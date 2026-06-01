<?php

declare(strict_types=1);

namespace AlexSkrypnyk\drupal_extension_scaffold\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests for the info devtools script.
 *
 * phpcs:disable Drupal.Commenting.FunctionComment.Missing
 * phpcs:disable Drupal.Commenting.DocComment.MissingShort
 */
#[RunTestsInSeparateProcesses]
#[Group('p0')]
final class InfoTest extends UnitTestCase {

  protected function setUp(): void {
    parent::setUp();
    require_once dirname(__DIR__, 4) . '/.devtools/helpers.php';
  }

  /**
   * Set up the standard mocks for an info run.
   *
   * @param array<string, string> $shell_exec_map
   *   Map of substring → full command output. The first key whose
   *   substring appears in the requested command wins. A literal
   *   '*' key acts as a catch-all default.
   * @param array<string, bool> $files
   *   Map of file path → existence; used for file_exists, is_file,
   *   and is_dir lookups.
   * @param string[] $info_files
   *   Files returned from glob('*.info.yml').
   * @param string $cwd
   *   Value to return from getcwd().
   * @param array<string, string> $file_contents
   *   Map of file path → contents, used by file_get_contents.
   * @param string|false $command_php_path
   *   Path returned by command_path('php'); FALSE means not found.
   */
  protected function configureInfoMocks(
    array $shell_exec_map,
    array $files = [],
    array $info_files = [],
    string $cwd = '/test/project',
    array $file_contents = [],
    string|false $command_php_path = '/usr/bin/php',
  ): void {
    $this->registerMock('getcwd', 'DrupalExtensionScaffold\\DevTools', fn(): string => $cwd);

    $this->registerMock('shell_exec', 'DrupalExtensionScaffold\\DevTools', function (string $cmd) use ($shell_exec_map): string {
      foreach ($shell_exec_map as $needle => $output) {
        if ($needle !== '*' && str_contains($cmd, $needle)) {
          return $output;
        }
      }

      return $shell_exec_map['*'] ?? '';
    });

    $this->registerMock('file_exists', 'DrupalExtensionScaffold\\DevTools', fn(string $path): bool => $files[$path] ?? FALSE);
    $this->registerMock('is_file', 'DrupalExtensionScaffold\\DevTools', fn(string $path): bool => $files[$path] ?? FALSE);
    $this->registerMock('is_dir', 'DrupalExtensionScaffold\\DevTools', fn(string $path): bool => $files[$path] ?? FALSE);
    $this->registerMock('glob', 'DrupalExtensionScaffold\\DevTools', fn(): array => $info_files);
    $this->registerMock('file_get_contents', 'DrupalExtensionScaffold\\DevTools', fn(string $file): string => $file_contents[$file] ?? '');

    $this->registerMock('exec', 'DrupalExtensionScaffold\\DevTools', function (string $cmd, ?array &$output = NULL, ?int &$code = NULL) use ($command_php_path): bool {
      $output ??= [];
      if ($command_php_path !== FALSE && str_contains($cmd, 'command -v php')) {
        $output[] = $command_php_path;
        $code = 0;

        return TRUE;
      }
      $code = 1;

      return FALSE;
    });
  }

  public function testFullyPopulatedEnvironment(): void {
    $this->envSet('WEBSERVER_HOST', 'example.com');
    $this->envSet('WEBSERVER_PORT', '9001');
    $this->envSet('DRUPAL_PROFILE', 'minimal');

    $cwd = '/test/project';
    $drush_bin = 'build/vendor/bin/drush';

    $this->configureInfoMocks(
      shell_exec_map: [
        'php -v' => "PHP 8.3.14 (cli) (built: ...)\n",
        'composer --version' => "Composer version 2.7.7 2024-06-10 22:11:12\n",
        'node --version' => "v20.18.0\n",
        'npm --version' => "10.8.2\n",
        '--version' => "Drush Commandline Tool 13.3.0\n",
        'status --field=drupal-version' => "11.3.11\n",
        'lsof -ti' => "php -S example.com:9001\n",
      ],
      files: [
        $drush_bin => TRUE,
        $cwd . '/build' => TRUE,
      ],
      info_files: ['your_extension.info.yml'],
      cwd: $cwd,
    );

    $output = $this->runInfo();

    $this->assertStringContainsString('Drupal version:     11.3.11', $output);
    $this->assertStringContainsString('PHP version:        8.3.14 (/usr/bin/php)', $output);
    $this->assertStringContainsString('Composer:           2.7.7', $output);
    $this->assertStringContainsString('Drush:              13.3.0', $output);
    $this->assertStringContainsString('Node / npm:         20.18.0 / 10.8.2', $output);
    $this->assertStringContainsString('Webserver host:     example.com (env)', $output);
    $this->assertStringContainsString('Webserver port:     9001 (env)', $output);
    $this->assertStringContainsString('Site URL:           http://example.com:9001', $output);
    $this->assertStringContainsString('XDebug:             disabled', $output);
    $this->assertStringContainsString('Build dir:          /test/project/build', $output);
    $this->assertStringContainsString('Database:           /tmp/site_your_extension.sqlite', $output);
    $this->assertStringContainsString('Drupal profile:     minimal', $output);
    $this->assertStringContainsString('ENVIRONMENT INFO', $output);
  }

  public function testReadsPortFromDotenvWithFileSource(): void {
    $cwd = '/test/project';

    $this->configureInfoMocks(
      shell_exec_map: [
        'php -v' => "PHP 8.3.14\n",
        'composer --version' => '',
        'node --version' => '',
        'npm --version' => '',
        'lsof -ti' => '',
      ],
      files: ['.env' => TRUE],
      info_files: [],
      cwd: $cwd,
      file_contents: ['.env' => "WEBSERVER_PORT=8123\n"],
    );

    $output = $this->runInfo();

    $this->assertStringContainsString('Webserver host:     localhost (default)', $output);
    $this->assertStringContainsString('Webserver port:     8123 (.env)', $output);
    $this->assertStringContainsString('Site URL:           http://localhost:8123', $output);
  }

  public function testFallsBackToDefaultsWhenNothingResolves(): void {
    $cwd = '/test/project';

    $this->configureInfoMocks(
      shell_exec_map: ['*' => ''],
      files: [],
      info_files: [],
      cwd: $cwd,
      command_php_path: FALSE,
    );

    $output = $this->runInfo();

    $this->assertStringContainsString('Drupal version:     -', $output);
    $this->assertStringContainsString('PHP version:        -', $output);
    $this->assertStringContainsString('Composer:           -', $output);
    $this->assertStringContainsString('Drush:              -', $output);
    $this->assertStringContainsString('Node / npm:         - / -', $output);
    $this->assertStringContainsString('Webserver host:     localhost (default)', $output);
    $this->assertStringContainsString('Webserver port:     8000 (default)', $output);
    $this->assertStringContainsString('Site URL:           http://localhost:8000', $output);
    $this->assertStringContainsString('XDebug:             -', $output);
    $this->assertStringContainsString('Build dir:          -', $output);
    $this->assertStringContainsString('Database:           -', $output);
    $this->assertStringContainsString('Drupal profile:     standard', $output);
  }

  public function testDrushAbsentSuppressesPhpPathSuffixOnlyWhenPhpAlsoMissing(): void {
    // PHP is detectable but command_path('php') returns FALSE - the path
    // suffix is omitted.
    $cwd = '/test/project';

    $this->configureInfoMocks(
      shell_exec_map: [
        'php -v' => "PHP 8.3.14\n",
        '*' => '',
      ],
      files: [],
      info_files: [],
      cwd: $cwd,
      command_php_path: FALSE,
    );

    $output = $this->runInfo();

    $this->assertStringContainsString("PHP version:        8.3.14\n", $output);
    $this->assertStringNotContainsString('PHP version:        8.3.14 (', $output);
  }

  #[DataProvider('dataProviderXdebugStateDetection')]
  public function testXdebugStateDetection(string $ps_output, string $expected_state): void {
    $cwd = '/test/project';

    $this->configureInfoMocks(
      shell_exec_map: [
        'lsof -ti' => $ps_output,
        '*' => '',
      ],
      files: [],
      info_files: [],
      cwd: $cwd,
    );

    $output = $this->runInfo();

    $this->assertStringContainsString('XDebug:             ' . $expected_state, $output);
  }

  public static function dataProviderXdebugStateDetection(): \Iterator {
    yield 'no server listening' => ['ps_output' => '', 'expected_state' => '-'];
    yield 'server running without xdebug' => [
      'ps_output' => "php -S localhost:8000\n",
      'expected_state' => 'disabled',
    ];
    yield 'server running with xdebug' => [
      'ps_output' => "php -d xdebug.mode=debug -d xdebug.start_with_request=yes -S localhost:8000\n",
      'expected_state' => 'enabled',
    ];
  }

  public function testDrushDrupalVersionFallbackWhenStatusReturnsNothing(): void {
    // Drush binary exists but `drush status --field=drupal-version` returns
    // empty (e.g. before site install). Drush version is still reported.
    $cwd = '/test/project';
    $drush_bin = 'build/vendor/bin/drush';

    $this->configureInfoMocks(
      shell_exec_map: [
        '--version' => "Drush Commandline Tool 13.3.0\n",
        'status --field=drupal-version' => '',
        '*' => '',
      ],
      files: [
        $drush_bin => TRUE,
      ],
      info_files: [],
      cwd: $cwd,
    );

    $output = $this->runInfo();

    $this->assertStringContainsString('Drush:              13.3.0', $output);
    $this->assertStringContainsString('Drupal version:     -', $output);
  }

  /**
   * Run the info script and capture its output.
   */
  protected function runInfo(): string {
    ob_start();
    require dirname(__DIR__, 4) . '/.devtools/info';

    return (string) ob_get_clean();
  }

}
