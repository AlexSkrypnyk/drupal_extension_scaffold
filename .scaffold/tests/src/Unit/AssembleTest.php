<?php

declare(strict_types=1);

namespace AlexSkrypnyk\drupal_extension_scaffold\Tests\Unit;

use AlexSkrypnyk\drupal_extension_scaffold\Tests\Exceptions\QuitErrorException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests for the assemble devtools script.
 *
 * phpcs:disable Drupal.Commenting.FunctionComment.Missing
 * phpcs:disable Drupal.Commenting.DocComment.MissingShort
 */
#[RunTestsInSeparateProcesses]
#[Group('p0')]
final class AssembleTest extends UnitTestCase {

  /**
   * @var array<int, string>
   */
  protected array $capturedBuildComposerJson = [];

  protected function setUp(): void {
    parent::setUp();
    require_once dirname(__DIR__, 4) . '/.devtools/helpers.php';
  }

  /**
   * Build mocks and expected passthru sequence for an assemble run.
   *
   * @param array $config
   *   Configuration with keys: extension_name, extension_type, drupal_version,
   *   has_build_dir, has_patches,
   *   github_token, has_suggestions, has_deprecations_disabled,
   *   has_package_lock, has_skip_npm_build, has_nvmrc, has_node_modules,
   *   tool_files, has_version_specific_phpunit, has_polyfill_bootstrap.
   */
  protected function setupAssembleMocks(array $config): void {
    $config += [
      'extension_name' => 'test_extension',
      'extension_type' => 'module',
      'drupal_version' => '11',
      'has_build_dir' => FALSE,
      'has_patches' => FALSE,
      'github_token' => '',
      'suggestions' => [],
      'has_deprecations_disabled' => FALSE,
      'has_package_lock' => FALSE,
      'has_skip_npm_build' => FALSE,
      'has_nvmrc' => FALSE,
      'has_node_modules' => FALSE,
      'tool_files' => ['phpcs.xml', 'phpunit.xml'],
      'has_version_specific_phpunit' => FALSE,
      'has_polyfill_bootstrap' => FALSE,
    ];

    $cwd = '/test/project';
    $drupal_version_major = explode('.', explode('@', (string) $config['drupal_version'])[0])[0];

    // Build composer.json content.
    $composer_json = ['name' => 'drupal/' . $config['extension_name']];
    if ($config['suggestions'] !== []) {
      $composer_json['suggest'] = $config['suggestions'];
    }
    $composer_json_str = json_encode($composer_json, JSON_THROW_ON_ERROR);

    // Build/composer.json content (scaffold).
    $build_composer_json = json_encode([
      'repositories' => [
        ['type' => 'composer', 'url' => 'https://packages.drupal.org/8'],
      ],
      'require' => ['drupal/core-recommended' => '^11', 'drupal/core-composer-scaffold' => '^11'],
      'require-dev' => ['drupal/core-dev' => '^11'],
      'minimum-stability' => 'stable',
      'prefer-stable' => TRUE,
    ], JSON_THROW_ON_ERROR);

    $dev_composer_json = json_encode(['require-dev' => ['drupal/coder' => '^8']], JSON_THROW_ON_ERROR);

    // Mock getcwd().
    $this->registerMock('getcwd', 'DrupalExtensionScaffold\\DevTools', fn(): string => $cwd);

    // Mock exec for command_must_exist.
    $this->registerMock('exec', 'DrupalExtensionScaffold\\DevTools', function (string $cmd, ?array &$output = NULL, ?int &$code = NULL): string {
      $output ??= [];
      $code = 0;
      if (str_contains($cmd, 'command -v composer')) {
        $output[] = '/usr/bin/composer';
      }
      return '';
    });

    // Mock glob - for extension_info and symlink section.
    $this->registerMock('glob', 'DrupalExtensionScaffold\\DevTools', function (string $pattern) use ($config, $cwd): array {
      if ($pattern === '*.info.yml') {
        return [$config['extension_name'] . '.info.yml'];
      }
      if ($pattern === $cwd . '/*') {
        return [$cwd . '/src', $cwd . '/composer.json', $cwd . '/build'];
      }
      return [];
    });

    // Mock file_exists - multiple contexts.
    $all_tool_files = ['.eslintignore', '.eslintrc.json', '.prettierignore', '.prettierrc.json', '.stylelintrc.js', '.twig-cs-fixer.php', 'package-lock.json', 'package.json', 'phpcs.xml', 'phpstan.neon', 'phpmd.xml', 'phpunit.xml', 'rector.php'];
    $this->registerMock('file_exists', 'DrupalExtensionScaffold\\DevTools', function (string $file) use ($config, $all_tool_files, $drupal_version_major) {
      if ($file === 'composer.json') {
        return TRUE;
      }
      if (str_ends_with($file, '.info.yml')) {
        return TRUE;
      }
      if ($file === 'build/package-lock.json') {
        return $config['has_package_lock'];
      }
      if ($file === '.skip_npm_build') {
        return $config['has_skip_npm_build'];
      }
      if ($file === '.nvmrc') {
        return $config['has_nvmrc'];
      }
      $version_specific = 'phpunit.d' . $drupal_version_major . '.xml';
      if ($file === $version_specific) {
        return $config['has_version_specific_phpunit'];
      }
      if (str_contains($file, 'polyfill-php83/bootstrap.php')) {
        return $config['has_polyfill_bootstrap'];
      }
      if (in_array($file, $all_tool_files, TRUE)) {
        return in_array($file, $config['tool_files'], TRUE);
      }
      return FALSE;
    });

    // Mock is_dir - multiple contexts.
    // Track 'build' dir calls: first call from assemble returns config value,
    // subsequent calls from chmod_recursive/remove_dir return false to avoid
    // real filesystem iteration.
    $build_dir_checked = FALSE;
    $this->registerMock('is_dir', 'DrupalExtensionScaffold\\DevTools', function (string $path) use ($config, &$build_dir_checked) {
      if ($path === 'build') {
        if (!$build_dir_checked) {
          $build_dir_checked = TRUE;
          return $config['has_build_dir'];
        }
        return FALSE;
      }
      if ($path === 'patches') {
        return $config['has_patches'];
      }
      if ($path === 'build/patches') {
        return FALSE;
      }
      if ($path === 'build/web/modules/custom' || $path === 'build/web/themes/custom') {
        return FALSE;
      }
      if (str_contains($path, '/custom')) {
        // For the symlink section remove_dir check.
        return TRUE;
      }
      if ($path === 'build/node_modules') {
        return $config['has_node_modules'];
      }
      return FALSE;
    });

    // Mock mkdir.
    $this->registerMock('mkdir', 'DrupalExtensionScaffold\\DevTools', fn(): true => TRUE);

    // Mock file_get_contents - different files.
    $info_content = $config['extension_type'] === 'theme' ? "name: Test\ntype: theme\n" : "name: Test\ntype: module\n";
    $this->registerMock('file_get_contents', 'DrupalExtensionScaffold\\DevTools', function (string $file) use ($info_content, $composer_json_str, $build_composer_json, $dev_composer_json) {
      if (str_ends_with($file, '.info.yml')) {
        return $info_content;
      }
      if ($file === 'composer.json') {
        return $composer_json_str;
      }
      if ($file === 'build/composer.json') {
        return $build_composer_json;
      }
      if ($file === 'composer.dev.json') {
        return $dev_composer_json;
      }
      if (str_contains($file, 'default.settings.php') || str_contains($file, 'index.php') || str_contains($file, 'bootstrap.php')) {
        return "<?php\n";
      }
      return '';
    });

    // Mock file_put_contents - capture writes to build/composer.json.
    $this->capturedBuildComposerJson = [];
    $this->registerMock('file_put_contents', 'DrupalExtensionScaffold\\DevTools', function (string $file, string $content): int {
      if ($file === 'build/composer.json') {
        $this->capturedBuildComposerJson[] = $content;
      }
      return 100;
    });

    // Mock copy.
    $this->registerMock('copy', 'DrupalExtensionScaffold\\DevTools', fn(): true => TRUE);

    // Mock symlink.
    $this->registerMock('symlink', 'DrupalExtensionScaffold\\DevTools', fn(): true => TRUE);

    // Mock putenv.
    $this->registerMock('putenv', 'DrupalExtensionScaffold\\DevTools', fn(): true => TRUE);

    // Mock unlink (for removing composer.lock after create-project).
    $this->registerMock('unlink', 'DrupalExtensionScaffold\\DevTools', fn(): true => TRUE);

    // Build passthru sequence.
    $passthru_responses = [];

    // 1. composer validate.
    $passthru_responses[] = ['cmd' => 'composer validate --ansi --strict'];

    // 2. composer create-project.
    $drupal_version = $config['drupal_version'] ?? '11';
    $passthru_responses[] = ['cmd' => sprintf('composer create-project %s build --no-install --no-interaction', escapeshellarg('drupal/recommended-project:~' . $drupal_version))];

    // 3. Patches copy (if applicable).
    if ($config['has_patches']) {
      // copy_dir is called but it's a real function that uses PHP iterators,
      // so we don't need to mock it separately - tested in HelpersCopyDirTest.
    }

    // 5. GitHub token (if applicable).
    if ($config['github_token'] !== '') {
      $passthru_responses[] = ['cmd' => sprintf('composer config --global github-oauth.github.com %s', escapeshellarg((string) $config['github_token']))];
    }

    // 6. composer install.
    $passthru_responses[] = ['cmd' => 'composer --working-dir=build install'];

    // 7. Suggested dependencies.
    foreach (array_keys($config['suggestions']) as $suggest) {
      $passthru_responses[] = ['cmd' => sprintf('composer --working-dir=build require %s', escapeshellarg((string) $suggest))];
    }

    // 8. NPM install and build (if applicable).
    if ($config['has_package_lock'] && !$config['has_skip_npm_build']) {
      $cmd = $config['has_nvmrc'] ? 'nvm use && ' : '';
      $cmd .= $config['has_node_modules'] ? '' : 'npm --prefix build ci && ';
      $cmd .= 'npm --prefix build run build';
      $passthru_responses[] = ['cmd' => $cmd];
    }

    $this->mockPassthruMultiple($passthru_responses);
  }

  #[DataProvider('dataProviderAssembleSuccess')]
  public function testAssembleSuccess(array $env, array $config): void {
    foreach ($env as $name => $value) {
      $this->envSet($name, $value);
    }

    // Ensure GITHUB_TOKEN is controlled - use envSet with empty string
    // to ensure it overrides any inherited env vars.
    $this->envSet('GITHUB_TOKEN', $config['github_token'] ?? '');

    // Ensure SYMFONY_DEPRECATIONS_HELPER is controlled.
    if ($config['has_deprecations_disabled'] ?? FALSE) {
      $this->envSet('SYMFONY_DEPRECATIONS_HELPER', 'disabled');
    }
    else {
      $this->envSet('SYMFONY_DEPRECATIONS_HELPER', '');
    }

    $this->setupAssembleMocks($config);

    // Create real directories for functions that use RecursiveDirectoryIterator
    // and cannot be mocked (copy_dir, remove_dir, chmod_recursive).
    $temp_dirs = [];
    if ($config['has_patches'] ?? FALSE) {
      @mkdir('patches');
      $temp_dirs[] = 'patches';
    }

    try {
      ob_start();
      require dirname(__DIR__, 4) . '/.devtools/assemble';
      $output = ob_get_clean();
    }
    finally {
      foreach ($temp_dirs as $temp_dir) {
        @rmdir($temp_dir);
      }
    }

    $this->assertIsString($output);
    $this->assertStringContainsString('ASSEMBLE', $output);
    $this->assertStringContainsString('Scaffold is valid', $output);
    $this->assertStringContainsString('Tools are valid', $output);
    $this->assertStringContainsString('Composer configuration is valid', $output);
    $this->assertStringContainsString('Drupal project created', $output);
    $this->assertStringContainsString('Dependencies installed', $output);
    $this->assertStringContainsString("Extension's code symlinked", $output);
    $this->assertStringContainsString('ASSEMBLE COMPLETE', $output);

    // Verify test dependencies were configured.
    $this->assertStringContainsString('Test dependencies configured', $output);
    $this->assertNotEmpty($this->capturedBuildComposerJson, 'Expected at least one write to build/composer.json');
    // Check that symfony/phpunit-bridge was added in one of the writes.
    $all_writes = implode("\n", $this->capturedBuildComposerJson);
    $this->assertStringContainsString('symfony/phpunit-bridge', $all_writes);

    $drupal_version = $env['DRUPAL_VERSION'] ?? '11';
    $this->assertStringContainsString('Creating Drupal ' . $drupal_version . ' project', $output);

    if ($config['has_build_dir']) {
      $this->assertStringContainsString('Removing existing build directory', $output);
    }

    if ($config['has_patches']) {
      $this->assertStringContainsString('Copying patches', $output);
    }

    if (($config['github_token'] ?? '') !== '') {
      $this->assertStringContainsString('GitHub authentication token', $output);
    }

    if ($config['has_deprecations_disabled'] ?? FALSE) {
      $this->assertStringContainsString('Disabling deprecation notices', $output);
    }

    if ($config['has_package_lock'] && !($config['has_skip_npm_build'] ?? FALSE)) {
      $this->assertStringContainsString('Processing front-end dependencies', $output);
      $this->assertStringContainsString('Front-end dependencies processed', $output);
    }
  }

  public static function dataProviderAssembleSuccess(): \Iterator {
    yield 'default Drupal 11 module' => [
      'env' => [],
      'config' => [
        'extension_type' => 'module',
        'github_token' => '',
        'suggestions' => [],
        'has_build_dir' => FALSE,
        'has_patches' => FALSE,
        'has_package_lock' => FALSE,
        'has_skip_npm_build' => FALSE,
        'tool_files' => ['phpcs.xml', 'phpunit.xml'],
      ],
    ];
    yield 'Drupal 10 theme with existing build dir' => [
      'env' => ['DRUPAL_VERSION' => '10'],
      'config' => [
        'drupal_version' => '10',
        'extension_type' => 'theme',
        'github_token' => '',
        'suggestions' => [],
        'has_build_dir' => TRUE,
        'has_patches' => FALSE,
        'has_package_lock' => FALSE,
        'has_skip_npm_build' => FALSE,
        'tool_files' => ['phpcs.xml', 'phpunit.xml'],
      ],
    ];
    yield 'with patches and GitHub token' => [
      'env' => [],
      'config' => [
        'extension_type' => 'module',
        'github_token' => 'ghp_test123',
        'suggestions' => [],
        'has_build_dir' => FALSE,
        'has_patches' => TRUE,
        'has_package_lock' => FALSE,
        'has_skip_npm_build' => FALSE,
        'tool_files' => ['phpcs.xml', 'phpunit.xml'],
      ],
    ];
    yield 'with suggested dependencies' => [
      'env' => [],
      'config' => [
        'extension_type' => 'module',
        'github_token' => '',
        'suggestions' => ['drupal/token' => 'Token support', 'drupal/pathauto' => 'Pathauto'],
        'has_build_dir' => FALSE,
        'has_patches' => FALSE,
        'has_package_lock' => FALSE,
        'has_skip_npm_build' => FALSE,
        'tool_files' => ['phpcs.xml', 'phpunit.xml'],
      ],
    ];
    yield 'with NPM build' => [
      'env' => [],
      'config' => [
        'extension_type' => 'module',
        'github_token' => '',
        'suggestions' => [],
        'has_build_dir' => FALSE,
        'has_patches' => FALSE,
        'has_package_lock' => TRUE,
        'has_skip_npm_build' => FALSE,
        'has_nvmrc' => FALSE,
        'tool_files' => ['phpcs.xml', 'phpunit.xml'],
      ],
    ];
    yield 'with NPM build and nvmrc' => [
      'env' => [],
      'config' => [
        'extension_type' => 'module',
        'github_token' => '',
        'suggestions' => [],
        'has_build_dir' => FALSE,
        'has_patches' => FALSE,
        'has_package_lock' => TRUE,
        'has_skip_npm_build' => FALSE,
        'has_nvmrc' => TRUE,
        'tool_files' => ['phpcs.xml', 'phpunit.xml'],
      ],
    ];
    yield 'with NPM build and existing node_modules' => [
      'env' => [],
      'config' => [
        'extension_type' => 'module',
        'github_token' => '',
        'suggestions' => [],
        'has_build_dir' => FALSE,
        'has_patches' => FALSE,
        'has_package_lock' => TRUE,
        'has_skip_npm_build' => FALSE,
        'has_node_modules' => TRUE,
        'tool_files' => ['phpcs.xml', 'phpunit.xml'],
      ],
    ];
    yield 'with skip NPM build' => [
      'env' => [],
      'config' => [
        'extension_type' => 'module',
        'github_token' => '',
        'suggestions' => [],
        'has_build_dir' => FALSE,
        'has_patches' => FALSE,
        'has_package_lock' => TRUE,
        'has_skip_npm_build' => TRUE,
        'tool_files' => ['phpcs.xml', 'phpunit.xml'],
      ],
    ];
    yield 'Drupal version with stability modifier' => [
      'env' => ['DRUPAL_VERSION' => '11@beta'],
      'config' => [
        'drupal_version' => '11@beta',
        'extension_type' => 'module',
        'github_token' => '',
        'suggestions' => [],
        'has_build_dir' => FALSE,
        'has_patches' => FALSE,
        'has_package_lock' => FALSE,
        'has_skip_npm_build' => FALSE,
        'tool_files' => ['phpcs.xml', 'phpunit.xml'],
      ],
    ];
    yield 'with deprecation notices disabled' => [
      'env' => [],
      'config' => [
        'extension_type' => 'module',
        'github_token' => '',
        'suggestions' => [],
        'has_build_dir' => FALSE,
        'has_patches' => FALSE,
        'has_package_lock' => FALSE,
        'has_skip_npm_build' => FALSE,
        'has_deprecations_disabled' => TRUE,
        'has_polyfill_bootstrap' => FALSE,
        'tool_files' => ['phpcs.xml', 'phpunit.xml'],
      ],
    ];
    yield 'with deprecation notices disabled and polyfill bootstrap' => [
      'env' => [],
      'config' => [
        'extension_type' => 'module',
        'github_token' => '',
        'suggestions' => [],
        'has_build_dir' => FALSE,
        'has_patches' => FALSE,
        'has_package_lock' => FALSE,
        'has_skip_npm_build' => FALSE,
        'has_deprecations_disabled' => TRUE,
        'has_polyfill_bootstrap' => TRUE,
        'tool_files' => ['phpcs.xml', 'phpunit.xml'],
      ],
    ];
    yield 'with all tool files' => [
      'env' => [],
      'config' => [
        'extension_type' => 'module',
        'github_token' => '',
        'suggestions' => [],
        'has_build_dir' => FALSE,
        'has_patches' => FALSE,
        'has_package_lock' => FALSE,
        'has_skip_npm_build' => FALSE,
        'tool_files' => ['phpcs.xml', 'phpstan.neon', 'phpmd.xml', 'rector.php', '.twig-cs-fixer.php', 'phpunit.xml'],
      ],
    ];
    yield 'with version-specific phpunit' => [
      'env' => ['DRUPAL_VERSION' => '10'],
      'config' => [
        'drupal_version' => '10',
        'extension_type' => 'module',
        'github_token' => '',
        'suggestions' => [],
        'has_build_dir' => FALSE,
        'has_patches' => FALSE,
        'has_package_lock' => FALSE,
        'has_skip_npm_build' => FALSE,
        'has_version_specific_phpunit' => TRUE,
        'tool_files' => ['phpcs.xml', 'phpunit.xml'],
      ],
    ];
    yield 'full complexity: all features enabled' => [
      'env' => ['DRUPAL_VERSION' => '10'],
      'config' => [
        'drupal_version' => '10',
        'extension_type' => 'theme',
        'github_token' => 'ghp_fulltest',
        'suggestions' => ['drupal/token' => 'Token'],
        'has_build_dir' => TRUE,
        'has_patches' => TRUE,
        'has_package_lock' => TRUE,
        'has_skip_npm_build' => FALSE,
        'has_nvmrc' => TRUE,
        'has_deprecations_disabled' => TRUE,
        'has_polyfill_bootstrap' => TRUE,
        'has_version_specific_phpunit' => TRUE,
        'tool_files' => ['phpcs.xml', 'phpstan.neon', 'phpmd.xml', 'rector.php', '.twig-cs-fixer.php', 'phpunit.xml'],
      ],
    ];
  }

  public function testAssembleMissingComposerJson(): void {
    putenv('GITHUB_TOKEN');
    putenv('SYMFONY_DEPRECATIONS_HELPER');

    // Override file_exists to return false for composer.json.
    $this->registerMock('file_exists', 'DrupalExtensionScaffold\\DevTools', fn(string $file): false => FALSE);

    $this->mockQuit(1);

    ob_start();
    try {
      require dirname(__DIR__, 4) . '/.devtools/assemble';
      $this->fail('Expected QuitErrorException to be thrown');
    }
    catch (QuitErrorException $e) {
      $this->assertEquals(1, $e->getCode());
    }
    finally {
      $output = ob_get_clean();
      $this->assertIsString($output);
      $this->assertStringContainsString('ASSEMBLE', $output);
      $this->assertStringContainsString('Missing composer.json file', $output);
    }
  }

}
