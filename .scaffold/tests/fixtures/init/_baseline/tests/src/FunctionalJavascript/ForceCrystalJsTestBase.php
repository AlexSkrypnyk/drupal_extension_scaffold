<?php

declare(strict_types=1);

namespace Drupal\Tests\force_crystal\FunctionalJavascript;

use PHPUnit\Framework\Attributes\Group;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Base class for ForceCrystal JavaScript functional tests.
 *
 * @group force_crystal
 */
#[Group('force_crystal')]
abstract class ForceCrystalJsTestBase extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['force_crystal'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    // Point the browser at the host's PHP webserver. With the default
    // 'chromedriver' backend the browser runs on the host, so 'localhost'
    // is reachable as-is. With the 'selenium' backend the browser runs
    // inside a container that cannot reach the host's 'localhost', so a
    // host-reachable address is used instead (host.docker.internal on
    // macOS, the docker bridge IP on Linux). WEBDRIVER_HOST overrides the
    // resolved host. In CircleCI all containers share the network
    // namespace, so no override is needed.
    if (!getenv('CIRCLECI')) {
      $port = getenv('WEBSERVER_PORT') ?: '8000';
      $host = getenv('WEBDRIVER_HOST');
      if ($host === FALSE || $host === '') {
        $backend = getenv('WEBDRIVER_BACKEND') ?: 'chromedriver';
        $host = $backend === 'selenium' ? (PHP_OS_FAMILY === 'Darwin' ? 'host.docker.internal' : '__VERSION__.1') : 'localhost';
      }
      putenv('SIMPLETEST_BASE_URL=http://' . $host . ':' . $port);
    }
    parent::setUp();
  }

  /**
   * {@inheritdoc}
   */
  protected function getMinkDriverArgs() {
    $args = parent::getMinkDriverArgs();
    if ($args === FALSE || $args === '') {
      return $args;
    }

    $decoded = json_decode($args, TRUE);
    if (!is_array($decoded) || !isset($decoded[2])) {
      return $args;
    }

    // The WebDriver endpoint is always reachable at 'localhost'; only the
    // port varies. The 'chromedriver' backend uses a per-project port
    // (WEBDRIVER_PORT, auto-discovered by '.devtools/chromedriver'), while
    // the 'selenium' container is always published on 4444.
    $backend = getenv('WEBDRIVER_BACKEND') ?: 'chromedriver';
    $port = $backend === 'selenium' ? '4444' : (getenv('WEBDRIVER_PORT') ?: '4444');
    $decoded[2] = 'http://localhost:' . $port;

    // The 'chromedriver' backend drives the host's Chrome directly, so it must
    // run headless to work on CI runners that have no display. The 'selenium'
    // container provides its own virtual display and stays headful.
    if ($backend !== 'selenium' && isset($decoded[1]['goog:chromeOptions']['args']) && is_array($decoded[1]['goog:chromeOptions']['args']) && !in_array('--headless=new', $decoded[1]['goog:chromeOptions']['args'], TRUE)) {
      $decoded[1]['goog:chromeOptions']['args'][] = '--headless=new';
    }

    return json_encode($decoded);
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
