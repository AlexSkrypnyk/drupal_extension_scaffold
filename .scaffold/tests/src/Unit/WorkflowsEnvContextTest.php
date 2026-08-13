<?php

declare(strict_types=1);

namespace AlexSkrypnyk\drupal_extension_scaffold\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\Yaml\Yaml;

/**
 * Tests that workflows define every environment variable they read.
 *
 * The `env` context resolves to an empty string for a name that was never
 * defined, so a typo or a self-reference silently disables the value instead
 * of failing the run. Neither actionlint nor Zizmor reports it.
 *
 * phpcs:disable Drupal.Commenting.FunctionComment.Missing
 * phpcs:disable Drupal.Commenting.DocComment.MissingShort
 */
#[Group('p0')]
final class WorkflowsEnvContextTest extends UnitTestCase {

  #[DataProvider('dataProviderEnvReferencesAreDefined')]
  public function testEnvReferencesAreDefined(string $path): void {
    $contents = file_get_contents($path);
    $this->assertIsString($contents);

    $parsed = Yaml::parse($contents);
    $this->assertIsArray($parsed);

    $defined = [];
    self::collectDefined($parsed, $defined);

    $undefined = array_values(array_unique(array_diff(self::collectReferenced($contents), $defined)));

    $this->assertSame([], $undefined, sprintf('%s reads environment variables that it never defines: %s', basename($path), implode(', ', $undefined)));
  }

  public static function dataProviderEnvReferencesAreDefined(): \Iterator {
    $paths = glob(dirname(__DIR__, 4) . '/.github/workflows/*.yml');

    foreach ($paths ?: [] as $path) {
      yield basename($path) => ['path' => $path];
    }
  }

  /**
   * Collect the names a workflow defines, into the passed array.
   *
   * @param mixed $node
   *   A node of the parsed workflow.
   * @param array<int, string> $defined
   *   Collected names.
   */
  protected static function collectDefined(mixed $node, array &$defined): void {
    if (!is_array($node)) {
      return;
    }

    foreach ($node as $key => $value) {
      if ($key === 'env' && is_array($value)) {
        foreach ($value as $name => $expression) {
          // A name assigned from its own `env` entry defines nothing: the
          // context does not yet hold the block being evaluated.
          if (is_string($name) && !self::isSelfReference($name, $expression)) {
            $defined[] = $name;
          }
        }
      }

      if ($key === 'run' && is_string($value) && str_contains($value, 'GITHUB_ENV')) {
        preg_match_all('/\b([A-Z_][A-Z0-9_]*)=/', $value, $matches);
        $defined = array_merge($defined, $matches[1]);
      }

      self::collectDefined($value, $defined);
    }
  }

  /**
   * Collect the names a workflow reads through the `env` context.
   *
   * @param string $contents
   *   The workflow contents.
   *
   * @return array<int, string>
   *   Collected names.
   */
  protected static function collectReferenced(string $contents): array {
    $referenced = [];

    preg_match_all('/\$\{\{(.*?)\}\}/s', $contents, $expressions);

    foreach ($expressions[1] as $expression) {
      preg_match_all('/\benv\.([A-Za-z_]\w*)/', $expression, $matches);
      $referenced = array_merge($referenced, $matches[1]);
    }

    return $referenced;
  }

  protected static function isSelfReference(string $name, mixed $expression): bool {
    if (!is_string($expression)) {
      return FALSE;
    }

    return preg_match('/^\s*\$\{\{\s*env\.' . preg_quote($name, '/') . '\s*\}\}\s*$/', $expression) === 1;
  }

}
