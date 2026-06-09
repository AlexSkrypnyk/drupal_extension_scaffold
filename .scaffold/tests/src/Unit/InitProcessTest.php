<?php

declare(strict_types=1);

namespace AlexSkrypnyk\drupal_extension_scaffold\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

use function main;
use function process;

/**
 * Class InitProcessTest.
 *
 * In-process tests for the high-level orchestration functions in init.php:
 * 'process()', 'process_readme()' and 'process_internal()'.
 *
 * The existing functional 'InitTest' exercises these paths end-to-end via
 * a subprocess and PCOV cannot capture coverage from there. The tests in
 * this class run the same logic in-process so PCOV records it.
 *
 * 'remove_self' is always passed as 'n' because '__FILE__' inside 'init.php'
 * resolves to the loaded path of the script (the project root copy), not
 * the copy inside SUT - passing 'y' would delete the source 'init.php'.
 */
#[Group('p0')]
final class InitProcessTest extends UnitTestCase {

  protected string $originalCwd;

  public static function setUpBeforeClass(): void {
    putenv('SCRIPT_RUN_SKIP=1');
    require_once dirname(__DIR__, 4) . '/init.php';
    parent::setUpBeforeClass();
  }

  protected function setUp(): void {
    parent::setUp();
    $this->originalCwd = getcwd() ?: '/';
    $project_root = dirname(__DIR__, 4);
    self::locationsCopy($project_root, self::$sut, [], [
      '.idea',
      '.logs',
      '.phpunit.cache',
      '.artifacts',
      'build',
    ]);
    chdir(self::$sut);
  }

  protected function tearDown(): void {
    chdir($this->originalCwd);
    parent::tearDown();
  }

  /**
   * @param array<string> $command_wrapper
   * @param array<string> $expected_exists
   * @param array<string> $expected_not_exists
   * @param array<string> $expected_claude_allow
   */
  #[DataProvider('dataProviderProcess')]
  public function testProcess(string $name, string $machine_name, string $type, string $ci_provider, array $command_wrapper, array $expected_exists, array $expected_not_exists, array $expected_claude_allow, bool $info_yml_has_base_theme): void {
    process($name, $machine_name, $type, $ci_provider, $command_wrapper, [], 'n');

    foreach ($expected_exists as $path) {
      $this->assertFileExists(self::$sut . '/' . $path, 'Expected to exist: ' . $path);
    }

    foreach ($expected_not_exists as $path) {
      $this->assertFileDoesNotExist(self::$sut . '/' . $path, 'Expected not to exist: ' . $path);
    }

    $claude_path = self::$sut . '/.claude/settings.json';
    $claude_content = file_get_contents($claude_path);
    $this->assertNotFalse($claude_content);
    $decoded = json_decode($claude_content, TRUE);
    $this->assertSame(['permissions' => ['allow' => $expected_claude_allow, 'deny' => []]], $decoded);

    if ($info_yml_has_base_theme) {
      $info_path = self::$sut . '/' . $machine_name . '.info.yml';
      $info = file_get_contents($info_path);
      $this->assertNotFalse($info);
      $this->assertStringEndsWith('base theme: false' . PHP_EOL, $info);
    }

    // Verify the bulk replacements ran: the info.yml should no longer carry
    // the placeholder machine name and the scaffold attribution link must
    // survive the global replacement.
    $info_path = self::$sut . '/' . $machine_name . '.info.yml';
    $info = (string) file_get_contents($info_path);
    $this->assertStringNotContainsString('your_extension', $info);

    $readme = (string) file_get_contents(self::$sut . '/README.md');
    $this->assertStringContainsString($name, $readme);
    $this->assertStringContainsString('[Drupal Extension Scaffold](https://github.com/AlexSkrypnyk/drupal_extension_scaffold)', $readme);
  }

  public static function dataProviderProcess(): \Iterator {
    $module_exists = [
      'my_extension.info.yml',
      'my_extension.install',
      'my_extension.libraries.yml',
      'my_extension.links.menu.yml',
      'my_extension.module',
      'my_extension.routing.yml',
      'my_extension.services.yml',
      'config/schema/my_extension.schema.yml',
      'src/Form/MyExtensionForm.php',
      'src/MyExtensionService.php',
      'tests/src/Unit/MyExtensionServiceUnitTest.php',
      'tests/src/Kernel/MyExtensionServiceKernelTest.php',
      'tests/src/Functional/MyExtensionFunctionalTest.php',
      'tests/src/FunctionalJavascript/MyExtensionJsTestBase.php',
      'tests/src/FunctionalJavascript/MyExtensionSmokeJsTest.php',
      'css/my_extension.css',
      'js/my_extension.js',
      'js/my_extension.test.js',
      'README.md',
      'init.php',
    ];

    $module_not_exists = [
      'your_extension.info.yml',
      'your_extension.install',
      'your_extension.module',
      'src/YourExtensionService.php',
      'src/Form/YourExtensionForm.php',
      'README.dist.md',
      'LICENSE.txt',
      '.scaffold',
      '.claude/skills',
      'tests/scaffold',
    ];

    yield 'module, gha, ahoy' => [
      'My Extension', 'my_extension', 'module', 'gha', ['ahoy'],
      array_merge($module_exists, ['.github/workflows', '.ahoy.yml']),
      array_merge($module_not_exists, ['.circleci', 'Makefile']),
      ['Bash(ahoy:*)', 'Bash(composer:*)'],
      FALSE,
    ];

    yield 'theme' => [
      'My Theme', 'my_theme', 'theme', 'gha', ['ahoy'],
      [
        'my_theme.info.yml',
        'my_theme.libraries.yml',
        'README.md',
        '.github/workflows',
        '.ahoy.yml',
      ],
      [
        'your_extension.info.yml',
        'my_theme.install',
        'my_theme.module',
        'my_theme.routing.yml',
        'my_theme.services.yml',
        'my_theme.links.menu.yml',
        'src/MyThemeService.php',
        '.circleci',
        'Makefile',
      ],
      ['Bash(ahoy:*)', 'Bash(composer:*)'],
      TRUE,
    ];

    yield 'module, circleci, ahoy' => [
      'My Extension', 'my_extension', 'module', 'circleci', ['ahoy'],
      array_merge($module_exists, ['.circleci', '.ahoy.yml']),
      array_merge($module_not_exists, ['.github/workflows', 'Makefile']),
      ['Bash(ahoy:*)', 'Bash(composer:*)'],
      FALSE,
    ];

    yield 'module, gha, makefile only' => [
      'My Extension', 'my_extension', 'module', 'gha', ['makefile'],
      array_merge($module_exists, ['.github/workflows', 'Makefile']),
      array_merge($module_not_exists, ['.circleci', '.ahoy.yml']),
      ['Bash(composer:*)', 'Bash(make:*)'],
      FALSE,
    ];

    yield 'module, gha, both wrappers' => [
      'My Extension', 'my_extension', 'module', 'gha', ['ahoy', 'makefile'],
      array_merge($module_exists, ['.github/workflows', '.ahoy.yml', 'Makefile']),
      array_merge($module_not_exists, ['.circleci']),
      ['Bash(ahoy:*)', 'Bash(composer:*)', 'Bash(make:*)'],
      FALSE,
    ];

    yield 'module, gha, no wrappers' => [
      'My Extension', 'my_extension', 'module', 'gha', [],
      array_merge($module_exists, ['.github/workflows']),
      array_merge($module_not_exists, ['.circleci', '.ahoy.yml', 'Makefile']),
      ['Bash(composer:*)'],
      FALSE,
    ];
  }

  /**
   * @param array<string> $tools_remove
   * @param array<string> $expected_not_exists
   * @param array<string> $expected_composer_dev_absent
   * @param array<string> $expected_package_json_absent
   * @param array<string> $expected_pipeline_absent
   */
  #[DataProvider('dataProviderRemoveTools')]
  public function testProcessRemovesTools(array $tools_remove, array $expected_not_exists, array $expected_composer_dev_absent, array $expected_package_json_absent, array $expected_pipeline_absent): void {
    process('My Extension', 'my_extension', 'module', 'gha', ['ahoy', 'makefile'], $tools_remove, 'n');

    foreach ($expected_not_exists as $path) {
      $this->assertFileDoesNotExist(self::$sut . '/' . $path, 'Expected removed: ' . $path);
    }

    $composer_dev = (string) file_get_contents(self::$sut . '/composer.dev.json');
    foreach ($expected_composer_dev_absent as $needle) {
      $this->assertStringNotContainsString($needle, $composer_dev, 'composer.dev.json should not contain: ' . $needle);
    }

    $package_json = (string) file_get_contents(self::$sut . '/package.json');
    foreach ($expected_package_json_absent as $needle) {
      $this->assertStringNotContainsString($needle, $package_json, 'package.json should not contain: ' . $needle);
    }

    $ahoy = (string) file_get_contents(self::$sut . '/.ahoy.yml');
    $makefile = (string) file_get_contents(self::$sut . '/Makefile');
    $gha = (string) file_get_contents(self::$sut . '/.github/workflows/test.yml');
    foreach ($expected_pipeline_absent as $needle) {
      $this->assertStringNotContainsString($needle, $ahoy, '.ahoy.yml should not contain: ' . $needle);
      $this->assertStringNotContainsString($needle, $makefile, 'Makefile should not contain: ' . $needle);
      $this->assertStringNotContainsString($needle, $gha, 'test.yml should not contain: ' . $needle);
    }
  }

  public static function dataProviderRemoveTools(): \Iterator {
    yield 'phpcs' => [
      ['phpcs'],
      ['phpcs.xml'],
      ['drupal/coder', 'drevops/phpcs-standard', 'phpcompatibility/php-compatibility'],
      [],
      ['vendor/bin/phpcs', 'vendor/bin/phpcbf'],
    ];

    yield 'phpstan' => [
      ['phpstan'],
      ['phpstan.neon'],
      ['mglaman/phpstan-drupal', 'phpstan/extension-installer'],
      [],
      ['vendor/bin/phpstan'],
    ];

    yield 'rector' => [
      ['rector'],
      ['rector.php'],
      ['palantirnet/drupal-rector'],
      [],
      ['vendor/bin/rector'],
    ];

    yield 'twigcs' => [
      ['twigcs'],
      ['.twig-cs-fixer.php'],
      ['vincentlanglet/twig-cs-fixer'],
      [],
      ['vendor/bin/twig-cs-fixer'],
    ];

    yield 'all php lint tools' => [
      ['phpcs', 'phpstan', 'rector', 'twigcs'],
      ['phpcs.xml', 'phpstan.neon', 'rector.php', '.twig-cs-fixer.php'],
      ['drupal/coder', 'mglaman/phpstan-drupal', 'palantirnet/drupal-rector', 'vincentlanglet/twig-cs-fixer'],
      [],
      ['vendor/bin/phpcs', 'vendor/bin/phpcbf', 'vendor/bin/phpstan', 'vendor/bin/rector', 'vendor/bin/twig-cs-fixer'],
    ];

    yield 'eslint' => [
      ['eslint'],
      ['.eslintrc.json', '.eslintignore', '.prettierrc.json', '.prettierignore'],
      [],
      ['eslint-config-airbnb-base', 'eslint-plugin-prettier', '"prettier"', 'lint-js'],
      [],
    ];

    yield 'stylelint' => [
      ['stylelint'],
      ['.stylelintrc.js'],
      [],
      ['stylelint-config-standard', 'stylelint-order', 'lint-css'],
      [],
    ];

    yield 'cspell' => [
      ['cspell'],
      ['.cspell.json'],
      [],
      ['"cspell"', 'lint-spell'],
      ['npm run lint-spell'],
    ];

    yield 'jest' => [
      ['jest'],
      ['jest.config.js', 'js/my_extension.test.js'],
      [],
      ['jest-environment-jsdom'],
      ['npm test'],
    ];

    yield 'eslint and stylelint' => [
      ['eslint', 'stylelint'],
      ['.eslintrc.json', '.stylelintrc.js'],
      [],
      ['"eslint"', '"stylelint"', 'npm run lint-js', 'npm run lint-css'],
      ['Running ESLint', 'NodeJS linters'],
    ];

    yield 'all npm tools' => [
      ['eslint', 'stylelint', 'cspell', 'jest'],
      ['.eslintrc.json', '.stylelintrc.js', '.cspell.json', 'jest.config.js', 'js/my_extension.test.js'],
      [],
      ['"eslint"', '"stylelint"', '"cspell"', '"jest"', 'devDependencies'],
      ['Running ESLint', 'NodeJS linters', 'npm run lint-spell', 'npm test'],
    ];

    // Removing PHPUnit also removes the FunctionalJavascript layer (Mink and
    // Selenium deps), per the normalisation in 'remove_tools()'.
    yield 'phpunit' => [
      ['phpunit'],
      ['phpunit.xml', 'phpunit.d10.xml', 'tests'],
      ['phpunit/phpunit', 'phpspec/prophecy-phpunit', 'mikey179/vfsstream', 'lullabot/mink-selenium2-driver', 'behat/mink'],
      [],
      ['vendor/bin/phpunit', 'selenium'],
    ];

    yield 'functional_javascript' => [
      ['functional_javascript'],
      ['tests/src/FunctionalJavascript'],
      ['behat/mink', 'lullabot/mink-selenium2-driver', 'symfony/browser-kit'],
      [],
      ['selenium', 'functional-javascript'],
    ];

    yield 'renovate' => [
      ['renovate'],
      ['renovate.json'],
      [],
      [],
      [],
    ];
  }

  public function testProcessThrowsOnInvalidClaudeSettingsJson(): void {
    file_put_contents(self::$sut . '/.claude/settings.json', '{invalid json');

    $this->expectException(\JsonException::class);
    process('My Extension', 'my_extension', 'module', 'gha', ['ahoy'], [], 'n');
  }

  public function testProcessThrowsOnInvalidClaudeSettingsStructure(): void {
    file_put_contents(self::$sut . '/.claude/settings.json', '{"foo": "bar"}');

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('Invalid .claude/settings.json structure.');
    process('My Extension', 'my_extension', 'module', 'gha', ['ahoy'], [], 'n');
  }

  public function testProcessSkipsWhenClaudeSettingsMissing(): void {
    @unlink(self::$sut . '/.claude/settings.json');

    process('My Extension', 'my_extension', 'module', 'gha', ['ahoy'], [], 'n');

    $this->assertFileExists(self::$sut . '/my_extension.info.yml');
    $this->assertFileDoesNotExist(self::$sut . '/.claude/settings.json');
  }

  /**
   * @param array<string> $argv
   */
  #[DataProvider('dataProviderMainHelp')]
  public function testMainHelp(array $argv): void {
    ob_start();
    main($argv);
    $output = (string) ob_get_clean();

    $this->assertStringContainsString('Drupal Extension Scaffold', $output);
    $this->assertStringContainsString('Usage:', $output);
    $this->assertStringContainsString('--help', $output);
    // The interactive flow must not run when help is requested.
    $this->assertFileExists(self::$sut . '/your_extension.info.yml');
    $this->assertFileDoesNotExist(self::$sut . '/my_extension.info.yml');
  }

  public static function dataProviderMainHelp(): \Iterator {
    yield 'help' => [['init.php', 'help']];
    yield 'long flag' => [['init.php', '--help']];
    yield 'short flag h' => [['init.php', '-h']];
    yield 'short flag question' => [['init.php', '-?']];
  }

}
