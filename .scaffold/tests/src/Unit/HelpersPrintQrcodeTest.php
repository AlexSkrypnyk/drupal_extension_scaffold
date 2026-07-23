<?php

declare(strict_types=1);

namespace AlexSkrypnyk\drupal_extension_scaffold\Tests\Unit;

use function DrupalExtensionScaffold\DevTools\print_qrcode;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests for the print_qrcode() helper.
 *
 * Runs in isolated processes so the exec() mock that stands in for the
 * `qrencode` probe is not polluted by sibling tests that invoke the real
 * command_path() in the shared process.
 *
 * phpcs:disable Drupal.Commenting.FunctionComment.Missing
 * phpcs:disable Drupal.Commenting.DocComment.MissingShort
 */
#[RunTestsInSeparateProcesses]
#[CoversFunction('DrupalExtensionScaffold\DevTools\print_qrcode')]
#[Group('p0')]
final class HelpersPrintQrcodeTest extends UnitTestCase {

  protected function setUp(): void {
    parent::setUp();
    require_once dirname(__DIR__, 4) . '/.devtools/helpers.php';
  }

  protected function tearDown(): void {
    self::envReset();
    parent::tearDown();
  }

  public function testEmptyUrlRendersNothing(): void {
    // An empty URL short-circuits before the opt-in check or any probe.
    ob_start();
    print_qrcode('');
    $output = ob_get_clean();
    $this->assertIsString($output);

    $this->assertSame('', $output);
  }

  public function testDisabledByDefaultRendersNothing(): void {
    // QRCODE unset in both the environment and '.env': opt-in means nothing is
    // drawn, and qrencode is never even probed.
    $this->envUnset('QRCODE');
    $this->mockDotenvAbsent();

    ob_start();
    print_qrcode('https://example.com');
    $output = ob_get_clean();
    $this->assertIsString($output);

    $this->assertSame('', $output);
  }

  public function testExplicitlyDisabledRendersNothing(): void {
    $this->envSet('QRCODE', '0');

    ob_start();
    print_qrcode('https://example.com');
    $output = ob_get_clean();
    $this->assertIsString($output);

    $this->assertSame('', $output);
  }

  public function testEnabledButQrencodeAbsentRendersNothing(): void {
    $this->envSet('QRCODE', '1');
    $this->mockQrencodeAvailable(FALSE);

    ob_start();
    print_qrcode('https://example.com');
    $output = ob_get_clean();
    $this->assertIsString($output);

    $this->assertSame('', $output);
  }

  public function testEnabledViaEnvRendersCode(): void {
    $this->envSet('QRCODE', '1');
    $this->mockQrencodeAvailable(TRUE);
    $this->mockPassthru([
      'cmd' => "qrencode -t ANSIUTF8 'https://example.com'",
      'output' => '[QR-CODE]',
    ]);

    ob_start();
    print_qrcode('https://example.com');
    $output = ob_get_clean();
    $this->assertIsString($output);

    $this->assertStringContainsString('[QR-CODE]', $output);
  }

  public function testEnabledViaDotenvRendersCode(): void {
    $this->envUnset('QRCODE');
    $this->registerMock('file_exists', 'DrupalExtensionScaffold\\DevTools', fn(string $file): bool => $file === '.env');
    $this->registerMock('file_get_contents', 'DrupalExtensionScaffold\\DevTools', fn(string $file): string => $file === '.env' ? "QRCODE=1\n" : '');
    $this->mockQrencodeAvailable(TRUE);
    $this->mockPassthru([
      'cmd' => "qrencode -t ANSIUTF8 'https://example.com'",
      'output' => '[QR-CODE]',
    ]);

    ob_start();
    print_qrcode('https://example.com');
    $output = ob_get_clean();
    $this->assertIsString($output);

    $this->assertStringContainsString('[QR-CODE]', $output);
  }

  /**
   * Mock '.env' as not present so QRCODE resolves to its default.
   */
  protected function mockDotenvAbsent(): void {
    $this->registerMock('file_exists', 'DrupalExtensionScaffold\\DevTools', fn(): bool => FALSE);
  }

  /**
   * Mock command_path('qrencode') resolution via the underlying exec().
   */
  protected function mockQrencodeAvailable(bool $available): void {
    $this->registerMock('exec', 'DrupalExtensionScaffold\\DevTools', function (string $cmd, ?array &$output = NULL, ?int &$code = NULL) use ($available): bool {
      $output ??= [];
      if ($available && str_contains($cmd, 'command -v qrencode')) {
        $output[] = '/usr/bin/qrencode';
        $code = 0;

        return TRUE;
      }
      $code = 1;

      return FALSE;
    });
  }

}
