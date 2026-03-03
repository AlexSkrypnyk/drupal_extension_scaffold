<?php

declare(strict_types=1);

namespace AlexSkrypnyk\drupal_extension_scaffold\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests for the provision devtools script.
 *
 * phpcs:disable Drupal.Commenting.FunctionComment.Missing
 * phpcs:disable Drupal.Commenting.DocComment.MissingShort
 */
#[RunTestsInSeparateProcesses]
#[Group('p0')]
final class ProvisionTest extends UnitTestCase {

  protected function setUp(): void {
    parent::setUp();
    require_once dirname(__DIR__, 4) . '/.devtools/helpers.php';
  }

  /**
   * Build the drush command prefix.
   */
  protected static function drushPrefix(string $cwd): string {
    return 'build/vendor/bin/drush -r ' . escapeshellarg($cwd . '/build/web') . ' -y ';
  }

  #[DataProvider('dataProviderProvisionSuccess')]
  public function testProvisionSuccess(array $env, string $extension_type, string $db_status, array $suggested, bool $has_ahoy, bool $has_makefile): void {
    foreach ($env as $name => $value) {
      $this->envSet($name, $value);
    }

    $expected_host = $env['WEBSERVER_HOST'] ?? 'localhost';
    $expected_port = $env['WEBSERVER_PORT'] ?? '8000';
    $drupal_profile = $env['DRUPAL_PROFILE'] ?? 'standard';

    $extension_name = 'test_extension';
    $info_content = $extension_type === 'theme' ? "name: Test\ntype: theme\n" : "name: Test\ntype: module\n";
    $cwd = '/test/project';

    $this->registerMock('getcwd', 'DrupalExtensionScaffold\\DevTools', fn(): string => $cwd);

    // Mock glob for extension_info().
    $this->registerMock('glob', 'DrupalExtensionScaffold\\DevTools', fn(): array => [$extension_name . '.info.yml']);

    // Track file_get_contents calls.
    $composer_json = json_encode(['suggest' => $suggested], JSON_THROW_ON_ERROR);
    $file_get_contents_calls = 0;
    $this->registerMock('file_get_contents', 'DrupalExtensionScaffold\\DevTools', function (string $file) use (&$file_get_contents_calls, $info_content, $composer_json) {
      $file_get_contents_calls++;
      if (str_ends_with($file, '.info.yml')) {
        return $info_content;
      }
      if ($file === 'composer.json') {
        return $composer_json;
      }
      // Pre-warming cache call.
      if (str_starts_with($file, 'http://')) {
        return '<html></html>';
      }
      return '';
    });

    // Track file_exists calls.
    $this->registerMock('file_exists', 'DrupalExtensionScaffold\\DevTools', function (string $file) use ($has_ahoy, $has_makefile): bool {
      if ($file === '.ahoy.yml') {
        return $has_ahoy;
      }
      if ($file === 'Makefile') {
        return $has_makefile;
      }
      return FALSE;
    });

    // Build expected passthru commands.
    $prefix = self::drushPrefix($cwd);
    $passthru_responses = [];

    // 1. drush status --field=db-status.
    $passthru_responses[] = ['cmd' => $prefix . 'status --field=db-status', 'output' => $db_status];

    // 2. If connected, drop DB.
    if (trim($db_status) === 'Connected') {
      $passthru_responses[] = ['cmd' => $prefix . 'sql:drop -y'];
    }

    // 3. Site install.
    $db_file = '/tmp/site_' . $extension_name . '.sqlite';
    $passthru_responses[] = ['cmd' => $prefix . sprintf('site-install %s -y --db-url="sqlite://localhost/%s" --account-name=admin install_configure_form.enable_update_status_module=NULL install_configure_form.enable_update_status_emails=NULL', escapeshellarg($drupal_profile), $db_file)];

    // 4. drush status.
    $passthru_responses[] = ['cmd' => $prefix . 'status'];

    // 5. Enable extension (theme or module).
    if ($extension_type === 'theme') {
      $passthru_responses[] = ['cmd' => $prefix . 'theme:enable ' . escapeshellarg($extension_name)];
    }
    else {
      $passthru_responses[] = ['cmd' => $prefix . 'pm:enable ' . escapeshellarg($extension_name)];
    }

    // 6. Cache rebuild.
    $passthru_responses[] = ['cmd' => $prefix . 'cr'];

    // 7. Enable suggested modules.
    $drupal_suggestions = [];
    foreach (array_keys($suggested) as $suggest) {
      if (str_starts_with((string) $suggest, 'drupal/')) {
        $module_name = preg_replace('/^drupal\//', '', (string) $suggest);
        $module_name = explode(':', (string) $module_name)[0];
        $drupal_suggestions[] = $module_name;
        $passthru_responses[] = ['cmd' => $prefix . 'pm:enable ' . escapeshellarg($module_name)];
      }
    }

    // 8. drush uli.
    $passthru_responses[] = ['cmd' => $prefix . sprintf('uli -l http://%s:%s --no-browser', $expected_host, $expected_port), 'output' => 'http://' . $expected_host . ':' . $expected_port . '/user/reset/1/abc/login'];

    $this->mockPassthruMultiple($passthru_responses);

    ob_start();
    require dirname(__DIR__, 4) . '/.devtools/provision';
    $output = ob_get_clean();

    $this->assertIsString($output);
    $this->assertStringContainsString('PROVISION', $output);
    $this->assertStringContainsString('Installing Drupal into SQLite database', $output);
    $this->assertStringContainsString('Drupal installed', $output);
    $this->assertStringContainsString('Enabling extension ' . $extension_name, $output);
    $this->assertStringContainsString('Clearing caches', $output);
    $this->assertStringContainsString('Suggested modules enabled', $output);
    $this->assertStringContainsString('Caches pre-warmed', $output);
    $this->assertStringContainsString('PROVISION COMPLETE', $output);
    $this->assertStringContainsString('http://' . $expected_host . ':' . $expected_port, $output);

    if ($has_ahoy) {
      $this->assertStringContainsString('Run `ahoy` to see available commands', $output);
    }
    else {
      $this->assertStringNotContainsString('Run `ahoy`', $output);
    }

    if ($has_makefile) {
      $this->assertStringContainsString('Run `make` to see available commands', $output);
    }
    else {
      $this->assertStringNotContainsString('Run `make`', $output);
    }
  }

  public static function dataProviderProvisionSuccess(): \Iterator {
    yield 'module, fresh install, no suggestions, no helpers' => [
      'env' => [],
      'extension_type' => 'module',
      'db_status' => '',
      'suggested' => [],
      'has_ahoy' => FALSE,
      'has_makefile' => FALSE,
    ];
    yield 'module, connected DB, with suggestions, ahoy and make' => [
      'env' => [],
      'extension_type' => 'module',
      'db_status' => 'Connected',
      'suggested' => ['drupal/token' => 'Provides token support', 'drupal/pathauto' => 'Provides path aliases'],
      'has_ahoy' => TRUE,
      'has_makefile' => TRUE,
    ];
    yield 'theme, fresh install, no suggestions, ahoy only' => [
      'env' => [],
      'extension_type' => 'theme',
      'db_status' => '',
      'suggested' => [],
      'has_ahoy' => TRUE,
      'has_makefile' => FALSE,
    ];
    yield 'module, custom profile and port' => [
      'env' => ['DRUPAL_PROFILE' => 'minimal', 'WEBSERVER_PORT' => '9000', 'WEBSERVER_HOST' => '0.0.0.0'],
      'extension_type' => 'module',
      'db_status' => '',
      'suggested' => [],
      'has_ahoy' => FALSE,
      'has_makefile' => TRUE,
    ];
    yield 'module with non-drupal suggestions filtered out' => [
      'env' => [],
      'extension_type' => 'module',
      'db_status' => '',
      'suggested' => ['drupal/token' => 'Token support', 'ext-json' => 'JSON extension', 'drupal/views_bulk_operations' => 'VBO'],
      'has_ahoy' => FALSE,
      'has_makefile' => FALSE,
    ];
    yield 'module with drupal suggestion containing colon' => [
      'env' => [],
      'extension_type' => 'module',
      'db_status' => '',
      'suggested' => ['drupal/token:^2.0' => 'Token support with version constraint'],
      'has_ahoy' => FALSE,
      'has_makefile' => FALSE,
    ];
  }

}
