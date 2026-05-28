<?php

declare(strict_types=1);

namespace AlexSkrypnyk\drupal_extension_scaffold\Tests\Unit;

use function DrupalExtensionScaffold\DevTools\find_free_port;
use AlexSkrypnyk\drupal_extension_scaffold\Tests\Exceptions\QuitErrorException;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests for find_free_port() helper.
 *
 * The helper probes both IPv4 (127.0.0.1) and IPv6 ([::1]) loopback
 * interfaces per port and only returns a port that is free on both.
 *
 * @phpcs:disable Drupal.Classes.FullyQualifiedNamespace.UseStatementMissing
 * phpcs:disable Drupal.Commenting.FunctionComment.Missing
 * phpcs:disable Drupal.Commenting.DocComment.MissingShort
 */
#[CoversFunction('DrupalExtensionScaffold\DevTools\find_free_port')]
#[RunTestsInSeparateProcesses]
#[Group('p0')]
final class HelpersFindFreePortTest extends UnitTestCase {

  protected function setUp(): void {
    parent::setUp();
    require_once dirname(__DIR__, 4) . '/.devtools/helpers.php';
  }

  public function testFirstPortIsFreeOnBothStacks(): void {
    $this->mockStreamSocketServer([
      ['address' => '127.0.0.1', 'port' => 8000, 'success' => TRUE],
      ['address' => '[::1]', 'port' => 8000, 'success' => TRUE],
    ]);

    $port = find_free_port(8000, 100);
    $this->assertSame(8000, $port);
  }

  public function testPortBusyOnIpv4SkippedEvenIfIpv6Free(): void {
    // 8000 fails IPv4 probe; loop breaks before probing IPv6, moves to 8001.
    $this->mockStreamSocketServer([
      ['address' => '127.0.0.1', 'port' => 8000, 'success' => FALSE],
      ['address' => '127.0.0.1', 'port' => 8001, 'success' => TRUE],
      ['address' => '[::1]', 'port' => 8001, 'success' => TRUE],
    ]);

    $port = find_free_port(8000, 100);
    $this->assertSame(8001, $port);
  }

  public function testPortBusyOnIpv6OnlyIsRejected(): void {
    // 8000 passes IPv4 but fails IPv6 (real scenario: PHP -S already on
    // [::1]:8000). 8001 is free on both.
    $this->mockStreamSocketServer([
      ['address' => '127.0.0.1', 'port' => 8000, 'success' => TRUE],
      ['address' => '[::1]', 'port' => 8000, 'success' => FALSE],
      ['address' => '127.0.0.1', 'port' => 8001, 'success' => TRUE],
      ['address' => '[::1]', 'port' => 8001, 'success' => TRUE],
    ]);

    $port = find_free_port(8000, 100);
    $this->assertSame(8001, $port);
  }

  public function testCustomStartingPort(): void {
    $this->mockStreamSocketServer([
      ['address' => '127.0.0.1', 'port' => 9000, 'success' => FALSE],
      ['address' => '127.0.0.1', 'port' => 9001, 'success' => TRUE],
      ['address' => '[::1]', 'port' => 9001, 'success' => TRUE],
    ]);

    $port = find_free_port(9000, 100);
    $this->assertSame(9001, $port);
  }

  public function testInvalidStartPortBelowRangeFails(): void {
    $this->mockQuit(1);

    $this->expectException(QuitErrorException::class);
    ob_start();
    try {
      find_free_port(0, 100);
    }
    finally {
      $output = ob_get_clean();
      $this->assertIsString($output);
      $this->assertStringContainsString('Start port must be between 1 and 65535', $output);
    }
  }

  public function testInvalidStartPortAboveRangeFails(): void {
    $this->mockQuit(1);

    $this->expectException(QuitErrorException::class);
    ob_start();
    try {
      find_free_port(70000, 100);
    }
    finally {
      $output = ob_get_clean();
      $this->assertIsString($output);
      $this->assertStringContainsString('Start port must be between 1 and 65535', $output);
    }
  }

  public function testInvalidMaxAttemptsFails(): void {
    $this->mockQuit(1);

    $this->expectException(QuitErrorException::class);
    ob_start();
    try {
      find_free_port(8000, 0);
    }
    finally {
      $output = ob_get_clean();
      $this->assertIsString($output);
      $this->assertStringContainsString('Max attempts must be a positive integer', $output);
    }
  }

  public function testAllPortsBusyCallsFail(): void {
    $responses = [];
    for ($p = 8000; $p < 8005; $p++) {
      // First probe (IPv4) fails; loop moves on to next port.
      $responses[] = ['address' => '127.0.0.1', 'port' => $p, 'success' => FALSE];
    }
    $this->mockStreamSocketServer($responses);
    $this->mockQuit(1);

    $this->expectException(QuitErrorException::class);
    ob_start();
    try {
      find_free_port(8000, 5);
    }
    finally {
      $output = ob_get_clean();
      $this->assertIsString($output);
      $this->assertStringContainsString('Unable to find a free port in range 8000-8004', $output);
    }
  }

  /**
   * Mock stream_socket_server() to return FALSE or a resource per call.
   *
   * @param array<int, array{address: string, port: int, success: bool}> $responses
   *   Ordered list of expected calls. Each entry is the address and port
   *   to expect and whether the bind should succeed.
   */
  protected function mockStreamSocketServer(array $responses): void {
    $this->addMockResponses('stream_socket_server', $responses);

    if (isset($this->mocks['stream_socket_server'])) {
      return;
    }

    $this->registerMock('stream_socket_server', 'DrupalExtensionScaffold\\DevTools', function (string $address, &$errno = NULL, &$errstr = NULL) {
      $response = $this->getNextMockResponse('stream_socket_server');
      if (!is_string($response['address']) || !is_int($response['port']) || !is_bool($response['success'])) {
        throw new \RuntimeException('Mocked stream_socket_server response must have string "address", int "port" and bool "success".');
      }
      $expected = sprintf('tcp://%s:%d', $response['address'], $response['port']);
      if ($address !== $expected) {
        throw new \RuntimeException(sprintf('stream_socket_server() called with unexpected address. Expected "%s", got "%s".', $expected, $address));
      }

      if (!$response['success']) {
        $errno = 48;
        $errstr = 'Address already in use';

        return FALSE;
      }

      $stream = fopen('php://memory', 'r');
      if ($stream === FALSE) {
        throw new \RuntimeException('Unable to open php://memory stream for mock.');
      }

      return $stream;
    });
  }

}
