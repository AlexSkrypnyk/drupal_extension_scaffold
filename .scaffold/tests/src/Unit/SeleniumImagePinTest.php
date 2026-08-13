<?php

declare(strict_types=1);

namespace AlexSkrypnyk\drupal_extension_scaffold\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\Process\Process;

/**
 * Tests that every Selenium image reference resolves to one pinned digest.
 *
 * The image is a single dependency written out as a plain string in three
 * file formats that have no include mechanism, so the copies are held equal
 * by Renovate rather than by construction. A pin no manager can see falls
 * behind silently: nothing fails, and that job keeps pulling a browser the
 * rest of CI stopped using.
 *
 * phpcs:disable Drupal.Commenting.FunctionComment.Missing
 * phpcs:disable Drupal.Commenting.DocComment.MissingShort
 */
#[Group('p0')]
final class SeleniumImagePinTest extends UnitTestCase {

  /**
   * Image serving the WebDriver endpoint wherever the browser runs in Docker.
   */
  protected const IMAGE = 'selenium/standalone-chromium';

  /**
   * Files carrying a reference, relative to the repository root.
   */
  protected const FILES = [
    '.circleci/config.yml',
    '.devtools/browser',
    '.github/workflows/scaffold-test.yml',
    '.github/workflows/test.yml',
  ];

  /**
   * Files whose pin the Renovate `github-actions` manager cannot reach.
   *
   * That manager reads `uses:`, `container.image` and `services.<id>.image`,
   * so a digest inside a `run:` script, a CircleCI anchor or a PHP constant
   * needs the custom manager instead. `test.yml` is absent on purpose: its
   * `services.image` pin is already managed, and a second manager over the
   * same line would raise competing updates for it.
   */
  protected const CUSTOM_MANAGED = [
    '.circleci/config.yml',
    '.devtools/browser',
    '.github/workflows/scaffold-test.yml',
  ];

  /**
   * Paths the reference scan skips, as `git grep` pathspecs.
   *
   * `init.php` deletes `.scaffold/`, and its fixtures are generated from the
   * files above rather than maintained beside them. `renovate.json` names the
   * image inside the pattern that manages these pins, so it describes a
   * reference rather than being one.
   */
  protected const UNSCANNED = [
    ':!.scaffold/',
    ':!renovate.json',
  ];

  #[DataProvider('dataProviderFiles')]
  public function testReferenceIsPinned(string $file): void {
    $contents = self::read($file);

    $tagged = preg_match_all(sprintf('#%s:#', preg_quote(self::IMAGE, '#')), $contents);
    $pinned = preg_match_all(self::pinPattern(), $contents);

    $this->assertSame($tagged, $pinned, sprintf('%s references %s without a digest. Pin it to the digest the other files use.', $file, self::IMAGE));
  }

  public static function dataProviderFiles(): \Iterator {
    foreach (self::FILES as $file) {
      yield $file => ['file' => $file];
    }
  }

  public function testEveryReferenceSharesOneDigest(): void {
    $digests = [];

    foreach (self::FILES as $file) {
      foreach (self::digests($file) as $digest) {
        $digests[$digest][] = $file;
      }
    }

    $this->assertCount(1, $digests, sprintf("%s is pinned to more than one digest:\n%s", self::IMAGE, self::describe($digests)));
  }

  /**
   * Tests that no reference escapes the list the other assertions walk.
   *
   * Without this, a fifth copy added to a new file would be pinned to
   * whatever digest its author pasted and never checked against the rest.
   */
  public function testEveryReferenceIsListed(): void {
    $process = new Process(array_merge(['git', 'grep', '--files-with-matches', '--fixed-strings', self::IMAGE, '--'], self::UNSCANNED), self::root());
    $process->run();

    $found = array_values(array_filter(explode("\n", trim($process->getOutput()))));
    sort($found);

    $expected = self::FILES;
    sort($expected);

    $this->assertSame($expected, $found, sprintf('The set of files referencing %s has changed. Add or remove the file in %s::FILES so its digest is checked too.', self::IMAGE, self::class));
  }

  /**
   * Tests that Renovate is configured to bump the pins nothing else manages.
   *
   * A `managerFilePatterns` entry that matches no file, or a `matchStrings`
   * regex that matches no line, fails open: Renovate reports nothing and the
   * digest simply stops moving. Compiling the configured regex against the
   * real file is what turns that silence back into a failure.
   */
  #[DataProvider('dataProviderCustomManaged')]
  public function testRenovateManagesPin(string $file): void {
    $config = json_decode(self::read('renovate.json'), TRUE, 512, JSON_THROW_ON_ERROR);
    $this->assertIsArray($config);
    $this->assertContains('custom.regex', $config['enabledManagers'], 'renovate.json does not enable the custom regex manager, so its customManagers entries never run.');

    $manager = self::customManager($config, $file);
    $this->assertNotNull($manager, sprintf('No renovate.json customManagers entry lists %s, so Renovate would never bump its digest.', $file));

    $contents = self::read($file);
    $matched = [];

    foreach ($manager['matchStrings'] as $pattern) {
      if (preg_match(sprintf('#%s#', $pattern), $contents, $matches) === 1) {
        $matched[] = $matches['currentDigest'];
      }
    }

    $this->assertNotSame([], $matched, sprintf('The customManagers regex matches nothing in %s, so Renovate would report no dependency for it.', $file));
    $this->assertSame(self::digests($file), array_values(array_unique($matched)), sprintf('The customManagers regex reads a different digest than %s actually pins.', $file));
  }

  public static function dataProviderCustomManaged(): \Iterator {
    foreach (self::CUSTOM_MANAGED as $file) {
      yield $file => ['file' => $file];
    }
  }

  /**
   * Absolute path of the repository root.
   */
  protected static function root(): string {
    return dirname(__DIR__, 4);
  }

  /**
   * Read a repository file, failing the test when it is missing.
   */
  protected static function read(string $file): string {
    $contents = file_get_contents(self::root() . '/' . $file);
    self::assertIsString($contents, sprintf('%s could not be read.', $file));

    return $contents;
  }

  /**
   * Pattern matching a digest-pinned reference, capturing the digest.
   */
  protected static function pinPattern(): string {
    return sprintf('#%s:[\w][\w.\-]*@(?<currentDigest>sha256:[a-f0-9]{64})#', preg_quote(self::IMAGE, '#'));
  }

  /**
   * Distinct digests a file pins the image to.
   *
   * @param string $file
   *   Path relative to the repository root.
   *
   * @return array<int, string>
   *   Digests, in the order they appear.
   */
  protected static function digests(string $file): array {
    preg_match_all(self::pinPattern(), self::read($file), $matches);

    return array_values(array_unique($matches['currentDigest']));
  }

  /**
   * The customManagers entry covering a file, or NULL when none does.
   *
   * @param array<string, mixed> $config
   *   Decoded renovate.json.
   * @param string $file
   *   Path relative to the repository root.
   *
   * @return array<string, mixed>|null
   *   The matching entry.
   */
  protected static function customManager(array $config, string $file): ?array {
    foreach ($config['customManagers'] ?? [] as $manager) {
      if (in_array($file, $manager['managerFilePatterns'] ?? [], TRUE)) {
        return $manager;
      }
    }

    return NULL;
  }

  /**
   * Render digest-to-files pairs for a failure message.
   *
   * @param array<string, array<int, string>> $digests
   *   Files keyed by the digest they pin.
   */
  protected static function describe(array $digests): string {
    $lines = [];

    foreach ($digests as $digest => $files) {
      $lines[] = sprintf('  %s in %s', $digest, implode(', ', $files));
    }

    return implode("\n", $lines);
  }

}
