<?php

declare(strict_types=1);

namespace AlexSkrypnyk\drupal_extension_scaffold\Tests\Unit;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests for the stop devtools script.
 *
 * phpcs:disable Drupal.Commenting.FunctionComment.Missing
 * phpcs:disable Drupal.Commenting.DocComment.MissingShort
 */
#[RunTestsInSeparateProcesses]
#[Group('p0')]
final class StopTest extends UnitTestCase {

  protected function setUp(): void {
    parent::setUp();
    require_once dirname(__DIR__, 4) . '/.devtools/helpers.php';
  }

  public function testStopDefaultPort(): void {
    $this->mockPassthru([
      'cmd' => "lsof -ti:'8000' | xargs kill -9 2>/dev/null",
    ]);
    $this->mockSleep();

    ob_start();
    require dirname(__DIR__, 4) . '/.devtools/stop';
    $output = ob_get_clean();

    $this->assertIsString($output);
    $this->assertStringContainsString('STOP ENVIRONMENT', $output);
    $this->assertStringContainsString('Stopping previously started services', $output);
    $this->assertStringContainsString('Services stopped', $output);
    $this->assertStringContainsString('ENVIRONMENT STOPPED', $output);
  }

  public function testStopCustomPort(): void {
    $this->envSet('WEBSERVER_PORT', '9000');

    $this->mockPassthru([
      'cmd' => "lsof -ti:'9000' | xargs kill -9 2>/dev/null",
    ]);
    $this->mockSleep();

    ob_start();
    require dirname(__DIR__, 4) . '/.devtools/stop';
    $output = ob_get_clean();

    $this->assertIsString($output);
    $this->assertStringContainsString('STOP ENVIRONMENT', $output);
    $this->assertStringContainsString('ENVIRONMENT STOPPED', $output);
  }

}
