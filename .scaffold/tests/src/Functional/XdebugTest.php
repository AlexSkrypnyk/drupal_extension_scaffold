<?php

declare(strict_types=1);

namespace AlexSkrypnyk\drupal_extension_scaffold\Tests\Functional;

use PHPUnit\Framework\Attributes\Group;

/**
 * Tests for the XDebug step-debugging toggle.
 *
 * The CI job for this group installs the xdebug PHP extension via
 * `coverage: xdebug` with a baseline `xdebug.mode=off`. The `debug` command
 * overrides via the `-d xdebug.mode=debug` runtime flag, and the running
 * server is queried through a probe file dropped in the docroot.
 *
 * phpcs:disable Drupal.Commenting.FunctionComment.Missing
 * phpcs:disable Drupal.Commenting.DocComment.MissingShort
 */
#[Group('p5')]
final class XdebugTest extends DevtoolsTestCase {

  public function testAhoy(): void {
    $this->assertToggle('ahoy');
  }

  public function testMake(): void {
    $this->assertToggle('make');
  }

  protected function assertToggle(string $tool): void {
    self::assertTrue(extension_loaded('xdebug'), 'Xdebug PHP extension must be installed in the runner PHP for this test group.');

    $this->processRun($tool, ['assemble'], [], [], $this->longTimeout, $this->defaultIdleTimeout);
    $this->assertProcessSuccessful();
    $this->assertProcessAnyOutputContains('ASSEMBLE COMPLETE');

    $this->processRun($tool, ['start'], [], [], $this->defaultTimeout, $this->defaultIdleTimeout);
    $this->assertProcessSuccessful();
    $this->assertProcessAnyOutputContains('XDebug    : Disabled');

    // The Drupal `.ht.router.php` serves any file that physically exists, so
    // a PHP file dropped in the docroot runs in the same webserver whose
    // xdebug configuration we are toggling.
    $probe = self::$sut . '/build/web/_xdebug_check.php';
    file_put_contents($probe, '<?php echo ini_get("xdebug.mode") ?: "off";');

    $this->assertSame('off', $this->fetchProbe(), 'Baseline: xdebug.mode is off.');

    $this->processRun($tool, ['debug'], [], [], $this->defaultTimeout, $this->defaultIdleTimeout);
    $this->assertProcessSuccessful();
    $this->assertProcessAnyOutputContains('Enabled XDebug');
    $this->assertSame('debug', $this->fetchProbe(), 'After debug: xdebug.mode is debug.');

    $this->processRun($tool, ['debug'], [], [], $this->defaultTimeout, $this->defaultIdleTimeout);
    $this->assertProcessSuccessful();
    $this->assertProcessAnyOutputContains('XDebug is already enabled');
    $this->assertSame('debug', $this->fetchProbe(), 'After repeat debug: still debug.');

    $this->processRun($tool, ['start'], [], [], $this->defaultTimeout, $this->defaultIdleTimeout);
    $this->assertProcessSuccessful();
    $this->assertProcessAnyOutputContains('XDebug    : Disabled');
    $this->assertSame('off', $this->fetchProbe(), 'After start: xdebug.mode is off again.');
  }

  protected function fetchProbe(): string {
    $env = file_exists(self::$sut . '/.env') ? parse_ini_file(self::$sut . '/.env') : [];
    $port = (string) ($env['WEBSERVER_PORT'] ?? '8000');
    $body = @file_get_contents('http://localhost:' . $port . '/_xdebug_check.php');

    return $body === FALSE ? '' : trim($body);
  }

}
