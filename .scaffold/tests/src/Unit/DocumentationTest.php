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

    $documented = preg_match('/^\|.*`' . preg_quote($name, '/') . '`.*\|/m', $readme) === 1;

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
    $this->assertSame(1, preg_match('/bootstrap="([^"]+)"/', $contents, $bootstrap), sprintf('%s declares no bootstrap attribute.', basename($path)));

    $position = strpos($bootstrap[1], 'core/');
    $this->assertIsInt($position, sprintf('%s bootstraps outside core: %s', basename($path), $bootstrap[1]));

    $prefix = substr($bootstrap[1], 0, $position);

    preg_match_all('/^.*(?<!' . preg_quote($prefix, '/') . ')\bcore\/.*$/m', $contents, $unprefixed);

    $this->assertSame([], $unprefixed[0], sprintf('%s references core paths without the `%s` docroot prefix.', basename($path), $prefix));
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
