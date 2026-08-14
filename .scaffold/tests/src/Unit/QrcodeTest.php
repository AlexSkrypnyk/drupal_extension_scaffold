<?php

declare(strict_types=1);

namespace AlexSkrypnyk\drupal_extension_scaffold\Tests\Unit;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests for the qrcode devtools script.
 *
 * phpcs:disable Drupal.Commenting.FunctionComment.Missing
 * phpcs:disable Drupal.Commenting.DocComment.MissingShort
 */
#[RunTestsInSeparateProcesses]
#[Group('p0')]
final class QrcodeTest extends UnitTestCase {

  protected function setUp(): void {
    parent::setUp();
    require_once dirname(__DIR__, 4) . '/.devtools/helpers.php';
  }

  public function testQrcodeRendersQrcodeForUrlArgument(): void {
    // Invoking the command is the opt-in, so the QRCODE gate does not apply.
    $this->envSet('QRCODE', '0');
    $this->mockCommandAvailable('qrencode', TRUE);
    $this->mockPassthru([
      'cmd' => "qrencode -t ANSIUTF8 'https://example.com'",
      'output' => '[QR-CODE]',
    ]);

    $argv = ['qrcode', 'https://example.com'];
    ob_start();
    require dirname(__DIR__, 4) . '/.devtools/qrcode';
    $output = ob_get_clean();
    $this->assertIsString($output);

    $this->assertStringContainsString('[QR-CODE]', $output);
  }

  public function testQrcodeDoesNothingWithoutUrlArgument(): void {
    $argv = ['qrcode'];
    ob_start();
    require dirname(__DIR__, 4) . '/.devtools/qrcode';
    $output = ob_get_clean();
    $this->assertIsString($output);

    $this->assertSame('', $output);
  }

  public function testQrcodeIfEnabledDoesNothingWhenOptInUnset(): void {
    // QRCODE is unset in both the environment and '.env'.
    $this->envUnset('QRCODE');
    $this->registerMock('file_exists', 'DrupalExtensionScaffold\\DevTools', fn(): bool => FALSE);

    $argv = ['qrcode', '--if-enabled', 'https://example.com'];
    ob_start();
    require dirname(__DIR__, 4) . '/.devtools/qrcode';
    $output = ob_get_clean();
    $this->assertIsString($output);

    $this->assertSame('', $output);
  }

  public function testQrcodeIfEnabledRendersWhenOptInSet(): void {
    $this->envSet('QRCODE', '1');
    $this->mockCommandAvailable('qrencode', TRUE);
    $this->mockPassthru([
      'cmd' => "qrencode -t ANSIUTF8 'https://example.com'",
      'output' => '[QR-CODE]',
    ]);

    $argv = ['qrcode', '--if-enabled', 'https://example.com'];
    ob_start();
    require dirname(__DIR__, 4) . '/.devtools/qrcode';
    $output = ob_get_clean();
    $this->assertIsString($output);

    $this->assertStringContainsString('[QR-CODE]', $output);
  }

}
