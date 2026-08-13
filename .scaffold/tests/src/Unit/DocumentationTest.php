<?php

declare(strict_types=1);

namespace AlexSkrypnyk\drupal_extension_scaffold\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests that documentation stays consistent with the files it describes.
 *
 * A script can be added to `.devtools/` without ever reaching its README
 * table, and a path can be copied into a PHPUnit config without being rebased
 * onto the build root. Neither drift is reported by a linter, because each
 * file stays individually valid while the two disagree.
 *
 * phpcs:disable Drupal.Commenting.FunctionComment.Missing
 * phpcs:disable Drupal.Commenting.DocComment.MissingShort
 */
#[Group('p0')]
final class DocumentationTest extends UnitTestCase {

  #[DataProvider('dataProviderDevtoolsScriptsAreDocumented')]
  public function testDevtoolsScriptsAreDocumented(string $name): void {
    $readme = file_get_contents(self::rootDir() . '/.devtools/README.md');
    $this->assertIsString($readme);

    // Anchored to the entry column: a name mentioned in another row's
    // description is a cross-reference, not an entry of its own.
    $documented = preg_match('/^\|\s*`' . preg_quote($name, '/') . '`\s*\|/m', $readme) === 1;

    $this->assertTrue($documented, sprintf('.devtools/README.md has no table row for `%s`.', $name));
  }

  public static function dataProviderDevtoolsScriptsAreDocumented(): \Iterator {
    $paths = glob(self::rootDir() . '/.devtools/*') ?: [];

    self::assertNotSame([], $paths, 'No entries found in .devtools/.');

    foreach ($paths as $path) {
      $name = basename($path);

      if ($name === 'README.md') {
        continue;
      }

      yield $name => ['name' => $name];
    }
  }

  #[DataProvider('dataProviderPhpunitCorePathsUseDocrootPrefix')]
  public function testPhpunitCorePathsUseDocrootPrefix(string $path): void {
    $contents = file_get_contents($path);
    $this->assertIsString($contents);

    // These configs run from the build root rather than from the Drupal root,
    // so the docroot prefix carried by `bootstrap` applies to every other
    // core-relative path in the file, comments included.
    preg_match('/bootstrap="([^"]+)"/', $contents, $bootstrap);
    $bootstrap_path = $bootstrap[1] ?? '';

    $this->assertNotSame('', $bootstrap_path, sprintf('%s declares no bootstrap attribute.', basename($path)));

    $position = strpos($bootstrap_path, 'core/');
    $this->assertIsInt($position, sprintf('%s bootstraps outside core: %s', basename($path), $bootstrap_path));

    $prefix = substr($bootstrap_path, 0, $position);

    $unprefixed = [];

    foreach (explode("\n", $contents) as $index => $line) {
      // Match each reference together with the path segments leading into it,
      // so the prefix can be compared by value. A lookbehind cannot serve
      // here: PCRE requires a fixed-length one, and the prefix is derived.
      preg_match_all('/[A-Za-z0-9_.\/-]*\bcore\//', $line, $references);

      $offenders = array_filter($references[0], static fn(string $reference): bool => !str_starts_with($reference, $prefix));

      foreach ($offenders as $offender) {
        $unprefixed[] = sprintf('line %d: %s', $index + 1, $offender);
      }
    }

    $this->assertSame([], $unprefixed, sprintf('%s references core paths without the `%s` docroot prefix.', basename($path), $prefix));
  }

  public static function dataProviderPhpunitCorePathsUseDocrootPrefix(): \Iterator {
    $paths = glob(self::rootDir() . '/phpunit*.xml') ?: [];

    self::assertNotSame([], $paths, 'No PHPUnit configuration files found in the project root.');

    foreach ($paths as $path) {
      yield basename($path) => ['path' => $path];
    }
  }

  /**
   * Get the project root directory.
   *
   * @return string
   *   The absolute path to the project root.
   */
  protected static function rootDir(): string {
    return dirname(__DIR__, 4);
  }

}
