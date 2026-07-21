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

  public function testRendersQrcodeForUrlArgument(): void {
    $this->registerMock('exec', 'DrupalExtensionScaffold\\DevTools', function (string $cmd, ?array &$output = NULL, ?int &$code = NULL): bool {
      $output ??= [];
      if (str_contains($cmd, 'command -v qrencode')) {
        $output[] = '/usr/bin/qrencode';
        $code = 0;

        return TRUE;
      }
      $code = 1;

      return FALSE;
    });
    $this->mockPassthru([
      'cmd' => "qrencode -t ANSIUTF8 'https://example.com'",
      'output' => '[QR-CODE]',
    ]);

    $argv = ['qrcode', 'https://example.com'];
    ob_start();
    require dirname(__DIR__, 4) . '/.devtools/qrcode';
    $output = (string) ob_get_clean();

    $this->assertStringContainsString('[QR-CODE]', $output);
  }

  public function testRendersNothingWithoutUrlArgument(): void {
    $argv = ['qrcode'];
    ob_start();
    require dirname(__DIR__, 4) . '/.devtools/qrcode';
    $output = (string) ob_get_clean();

    $this->assertSame('', $output);
  }

}
