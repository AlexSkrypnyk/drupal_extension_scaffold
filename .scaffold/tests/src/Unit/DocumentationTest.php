<?php

declare(strict_types=1);

namespace AlexSkrypnyk\drupal_extension_scaffold\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests that documentation stays consistent with the files it describes.
 *
 * A script can be added to `.devtools/` without ever reaching its README
 * table: the directory and the table are edited independently, and neither
 * file becomes invalid when they disagree, so no linter reports the drift.
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
