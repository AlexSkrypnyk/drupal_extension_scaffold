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
 * Runs in isolated processes so the exec() mock for the `qrencode` probe is
 * not polluted by sibling tests. Those tests invoke the real command_path()
 * in the shared process.
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

  public function testEmptyUrlDoesNothing(): void {
    // An empty URL short-circuits before the opt-in check or any probe.
    ob_start();
    print_qrcode('');
    $output = ob_get_clean();
    $this->assertIsString($output);

    $this->assertSame('', $output);
  }

  public function testDisabledByDefaultDoesNothing(): void {
    // QRCODE is unset in both the environment and '.env'. The feature is
    // opt-in, so nothing is drawn and qrencode is not probed.
    $this->envUnset('QRCODE');
    $this->mockDotenvAbsent();

    ob_start();
    print_qrcode('https://example.com');
    $output = ob_get_clean();
    $this->assertIsString($output);

    $this->assertSame('', $output);
  }

  public function testExplicitlyDisabledDoesNothing(): void {
    $this->envSet('QRCODE', '0');

    ob_start();
    print_qrcode('https://example.com');
    $output = ob_get_clean();
    $this->assertIsString($output);

    $this->assertSame('', $output);
  }

  public function testEnabledButQrencodeMissingDoesNothing(): void {
    $this->envSet('QRCODE', '1');
    $this->mockCommandAvailable('qrencode', FALSE);

    ob_start();
    print_qrcode('https://example.com');
    $output = ob_get_clean();
    $this->assertIsString($output);

    $this->assertSame('', $output);
  }

  public function testEnabledViaEnvRendersCode(): void {
    $this->envSet('QRCODE', '1');
    $this->mockCommandAvailable('qrencode', TRUE);
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

  public function testForcedRendersWhileExplicitlyDisabled(): void {
    $this->envSet('QRCODE', '0');
    $this->mockCommandAvailable('qrencode', TRUE);
    $this->mockPassthru([
      'cmd' => "qrencode -t ANSIUTF8 'https://example.com'",
      'output' => '[QR-CODE]',
    ]);

    ob_start();
    print_qrcode('https://example.com', force: TRUE);
    $output = ob_get_clean();
    $this->assertIsString($output);

    $this->assertStringContainsString('[QR-CODE]', $output);
  }

  public function testForcedWithEmptyUrlDoesNothing(): void {
    // Nothing else can stop the render, so the empty URL has to.
    $this->mockCommandAvailable('qrencode', TRUE);
    $this->mockPassthruNever();

    ob_start();
    print_qrcode('', force: TRUE);
    $output = ob_get_clean();
    $this->assertIsString($output);

    $this->assertSame('', $output);
  }

  public function testForcedButQrencodeMissingDoesNothing(): void {
    $this->envSet('QRCODE', '0');
    $this->mockCommandAvailable('qrencode', FALSE);
    $this->mockPassthruNever();

    ob_start();
    print_qrcode('https://example.com', force: TRUE);
    $output = ob_get_clean();
    $this->assertIsString($output);

    $this->assertSame('', $output);
  }

  public function testEnabledViaDotenvRendersCode(): void {
    $this->envUnset('QRCODE');
    $this->registerMock('file_exists', 'DrupalExtensionScaffold\\DevTools', fn(string $file): bool => $file === '.env');
    $this->registerMock('file_get_contents', 'DrupalExtensionScaffold\\DevTools', fn(string $file): string => $file === '.env' ? "QRCODE=1\n" : '');
    $this->mockCommandAvailable('qrencode', TRUE);
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

}
