<?php

declare(strict_types=1);

namespace AlexSkrypnyk\drupal_extension_scaffold\Tests\Unit;

use function DrupalExtensionScaffold\DevTools\link_browser_output;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\Group;

#[CoversFunction('DrupalExtensionScaffold\DevTools\link_browser_output')]
#[Group('p0')]
final class HelpersLinkBrowserOutputTest extends UnitTestCase {

  protected function setUp(): void {
    parent::setUp();
    require_once dirname(__DIR__, 4) . '/.devtools/helpers.php';
  }

  public function testMissingWebrootDoesNothing(): void {
    $webroot = self::$tmp . '/web_' . uniqid();
    $logs = self::$tmp . '/logs_' . uniqid();

    link_browser_output($webroot, $logs);

    $this->assertDirectoryDoesNotExist($logs . '/browser_output');
    $this->assertDirectoryDoesNotExist($webroot);
  }

  public function testCreatesSymlinkFromScratch(): void {
    [$webroot, $logs] = $this->createWebroot();

    link_browser_output($webroot, $logs);

    $target = $logs . '/browser_output';
    $link = $webroot . '/sites/simpletest/browser_output';

    $this->assertDirectoryExists($target);
    $this->assertTrue(is_link($link));
    $this->assertSame($target, readlink($link));

    // Output written through the link lands in the logs directory.
    file_put_contents($link . '/page.html', 'dump');
    $this->assertFileExists($target . '/page.html');
  }

  public function testReusesExistingTargetAndSimpletestDir(): void {
    [$webroot, $logs] = $this->createWebroot();
    mkdir($logs . '/browser_output', 0755, TRUE);
    file_put_contents($logs . '/browser_output/existing.html', 'keep');
    mkdir($webroot . '/sites/simpletest', 0755, TRUE);

    link_browser_output($webroot, $logs);

    $link = $webroot . '/sites/simpletest/browser_output';
    $this->assertTrue(is_link($link));
    // The pre-existing target directory and its contents are preserved.
    $this->assertFileExists($logs . '/browser_output/existing.html');
  }

  public function testReplacesExistingDirectory(): void {
    [$webroot, $logs] = $this->createWebroot();
    $link = $webroot . '/sites/simpletest/browser_output';
    mkdir($link, 0755, TRUE);
    file_put_contents($link . '/stale.html', 'stale');

    link_browser_output($webroot, $logs);

    $this->assertTrue(is_link($link));
    $this->assertSame($logs . '/browser_output', readlink($link));
  }

  public function testReplacesExistingSymlink(): void {
    [$webroot, $logs] = $this->createWebroot();
    mkdir($webroot . '/sites/simpletest', 0755, TRUE);
    $link = $webroot . '/sites/simpletest/browser_output';
    $other = self::$tmp . '/other_' . uniqid();
    mkdir($other, 0755, TRUE);
    symlink($other, $link);

    link_browser_output($webroot, $logs);

    $this->assertTrue(is_link($link));
    $this->assertSame($logs . '/browser_output', readlink($link));
  }

  public function testReplacesExistingFile(): void {
    [$webroot, $logs] = $this->createWebroot();
    mkdir($webroot . '/sites/simpletest', 0755, TRUE);
    $link = $webroot . '/sites/simpletest/browser_output';
    file_put_contents($link, 'i am a file');

    link_browser_output($webroot, $logs);

    $this->assertTrue(is_link($link));
    $this->assertSame($logs . '/browser_output', readlink($link));
  }

  /**
   * Create a throwaway project layout with an assembled web root.
   *
   * @return array{0: string, 1: string}
   *   Tuple of [webroot, logs_dir] absolute paths.
   */
  protected function createWebroot(): array {
    $base = self::$tmp . '/proj_' . uniqid();
    $webroot = $base . '/build/web';
    mkdir($webroot, 0755, TRUE);

    return [$webroot, $base . '/.logs'];
  }

}
