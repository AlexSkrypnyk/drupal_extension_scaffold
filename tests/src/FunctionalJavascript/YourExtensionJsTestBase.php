<?php

declare(strict_types=1);

namespace Drupal\Tests\your_extension\FunctionalJavascript;

use PHPUnit\Framework\Attributes\Group;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Base class for YourExtension JavaScript functional tests.
 *
 * @group your_extension
 */
#[Group('your_extension')]
abstract class YourExtensionJsTestBase extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['your_extension'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    // Override SIMPLETEST_BASE_URL so that Chrome inside the Selenium
    // container can reach the PHP webserver on the host machine.
    // In CircleCI, all containers share the network namespace, so
    // 'localhost' works without override.
    if (!getenv('CIRCLECI')) {
      $port = getenv('WEBSERVER_PORT') ?: '8000';
      $host = PHP_OS_FAMILY === 'Darwin' ? 'host.docker.internal' : '172.17.0.1';
      putenv('SIMPLETEST_BASE_URL=http://' . $host . ':' . $port);
    }
    parent::setUp();
  }

  /**
   * Create a screenshot with an auto-generated filename.
   *
   * Filename format: {ms_timestamp}-{Class}-{method}-L{line}.png.
   *
   * @param bool $set_background_color
   *   (optional) Whether to set the background color. Defaults to TRUE.
   */
  protected function createAutoScreenshot(bool $set_background_color = TRUE): void {
    $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);

    $line = $trace[0]['line'] ?? 0;
    $class = str_replace('\\', '_', $trace[1]['class'] ?? 'Unknown');
    $method = $trace[1]['function'];
    $timestamp = (int) (microtime(TRUE) * 1000);

    $filename = sprintf('%d-%s-%s-L%d.png', $timestamp, $class, $method, $line);
    $path = \Drupal::root() . '/sites/simpletest/browser_output/' . $filename;

    $this->createScreenshot($path, $set_background_color);
    $this->assertFileExists($path);
  }

}
