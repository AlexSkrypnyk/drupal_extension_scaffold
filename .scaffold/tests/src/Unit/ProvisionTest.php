<?php

declare(strict_types=1);

namespace AlexSkrypnyk\drupal_extension_scaffold\Tests\Unit;

use function DrupalExtensionScaffold\DevTools\site_db_file;
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
    $cwd = self::$tmp . '/provision_' . uniqid();
    mkdir($cwd . '/build/web', 0755, TRUE);

    $this->registerMock('getcwd', 'DrupalExtensionScaffold\\DevTools', fn(): string => $cwd);

    $this->registerMock('glob', 'DrupalExtensionScaffold\\DevTools', fn(): array => [$extension_name . '.info.yml']);

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
      // The cache pre-warm fetches the site over HTTP.
      if (str_starts_with($file, 'http://')) {
        return '<html></html>';
      }
      return '';
    });

    $this->registerMock('file_exists', 'DrupalExtensionScaffold\\DevTools', function (string $file) use ($has_ahoy, $has_makefile): bool {
      if ($file === '.ahoy.yml') {
        return $has_ahoy;
      }
      if ($file === 'Makefile') {
        return $has_makefile;
      }
      return FALSE;
    });

    $prefix = self::drushPrefix($cwd);
    $passthru_responses = [];

    $passthru_responses[] = ['cmd' => $prefix . 'status --field=db-status', 'output' => $db_status];

    if (trim($db_status) === 'Connected') {
      $passthru_responses[] = ['cmd' => $prefix . 'sql:drop -y'];
    }

    $db_file = site_db_file($extension_name);
    $passthru_responses[] = ['cmd' => $prefix . sprintf('site-install %s -y --db-url=%s --account-name=admin install_configure_form.enable_update_status_module=NULL install_configure_form.enable_update_status_emails=NULL', escapeshellarg($drupal_profile), escapeshellarg('sqlite://localhost/' . $db_file))];

    $passthru_responses[] = ['cmd' => $prefix . 'status'];

    if ($extension_type === 'theme') {
      $passthru_responses[] = ['cmd' => $prefix . 'theme:enable ' . escapeshellarg($extension_name)];
    }
    else {
      $passthru_responses[] = ['cmd' => $prefix . 'pm:enable ' . escapeshellarg($extension_name)];
    }

    $passthru_responses[] = ['cmd' => $prefix . 'cr'];

    $drupal_suggestions = [];
    foreach (array_keys($suggested) as $suggest) {
      if (str_starts_with((string) $suggest, 'drupal/')) {
        $module_name = preg_replace('/^drupal\//', '', (string) $suggest);
        $module_name = explode(':', (string) $module_name)[0];
        $drupal_suggestions[] = $module_name;
        $passthru_responses[] = ['cmd' => $prefix . 'pm:enable ' . escapeshellarg($module_name)];
      }
    }

    $site_url = sprintf('http://%s:%s', $expected_host, $expected_port);
    $passthru_responses[] = ['cmd' => $prefix . sprintf('uli -l %s --no-browser', escapeshellarg($site_url)), 'output' => $site_url . '/user/reset/1/abc/login'];

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
    $this->assertStringContainsString('Browser test output linked', $output);
    $this->assertStringContainsString('PROVISION COMPLETE', $output);
    $this->assertStringContainsString('http://' . $expected_host . ':' . $expected_port, $output);

    $browser_output_link = $cwd . '/build/web/sites/simpletest/browser_output';
    $this->assertTrue(is_link($browser_output_link));
    $this->assertSame($cwd . '/.logs/browser_output', readlink($browser_output_link));

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

  public function testProvisionReadsPortFromDotenvWhenEnvUnset(): void {
    $extension_name = 'test_extension';
    $info_content = "name: Test\ntype: module\n";
    $cwd = '/test/project';
    $expected_host = 'localhost';
    $expected_port = '8123';

    $this->registerMock('getcwd', 'DrupalExtensionScaffold\\DevTools', fn(): string => $cwd);
    $this->registerMock('glob', 'DrupalExtensionScaffold\\DevTools', fn(): array => [$extension_name . '.info.yml']);

    $this->registerMock('file_get_contents', 'DrupalExtensionScaffold\\DevTools', function (string $file) use ($info_content): string {
      if (str_ends_with($file, '.info.yml')) {
        return $info_content;
      }
      if ($file === '.env') {
        return "WEBSERVER_PORT=8123\n";
      }
      if ($file === 'composer.json') {
        return json_encode(['suggest' => []], JSON_THROW_ON_ERROR);
      }
      if (str_starts_with($file, 'http://')) {
        return '<html></html>';
      }

      return '';
    });

    $this->registerMock('file_exists', 'DrupalExtensionScaffold\\DevTools', fn(string $file): bool => $file === '.env');

    $prefix = self::drushPrefix($cwd);
    $db_file = site_db_file($extension_name);
    $site_url = sprintf('http://%s:%s', $expected_host, $expected_port);
    $this->mockPassthruMultiple([
      ['cmd' => $prefix . 'status --field=db-status', 'output' => ''],
      ['cmd' => $prefix . sprintf('site-install %s -y --db-url=%s --account-name=admin install_configure_form.enable_update_status_module=NULL install_configure_form.enable_update_status_emails=NULL', escapeshellarg('standard'), escapeshellarg('sqlite://localhost/' . $db_file))],
      ['cmd' => $prefix . 'status'],
      ['cmd' => $prefix . 'pm:enable ' . escapeshellarg($extension_name)],
      ['cmd' => $prefix . 'cr'],
      ['cmd' => $prefix . sprintf('uli -l %s --no-browser', escapeshellarg($site_url)), 'output' => $site_url . '/user/reset/1/abc/login'],
    ]);

    ob_start();
    require dirname(__DIR__, 4) . '/.devtools/provision';
    $output = ob_get_clean();

    $this->assertIsString($output);
    $this->assertStringContainsString('http://' . $expected_host . ':' . $expected_port, $output);
  }

  public function testProvisionDisplaysTunnelUrlWhenSet(): void {
    $extension_name = 'test_extension';
    $info_content = "name: Test\ntype: module\n";
    $cwd = '/test/project';
    $tunnel_url = 'https://abc.trycloudflare.com';

    $this->registerMock('getcwd', 'DrupalExtensionScaffold\\DevTools', fn(): string => $cwd);
    $this->registerMock('glob', 'DrupalExtensionScaffold\\DevTools', fn(): array => [$extension_name . '.info.yml']);

    $this->registerMock('file_get_contents', 'DrupalExtensionScaffold\\DevTools', function (string $file) use ($info_content, $tunnel_url): string {
      if (str_ends_with($file, '.info.yml')) {
        return $info_content;
      }
      if ($file === '.env') {
        return "WEBSERVER_PORT=8000\nTUNNEL_URL=" . $tunnel_url . "\n";
      }
      if ($file === 'composer.json') {
        return json_encode(['suggest' => []], JSON_THROW_ON_ERROR);
      }
      if (str_starts_with($file, 'http://')) {
        return '<html></html>';
      }

      return '';
    });

    $this->registerMock('file_exists', 'DrupalExtensionScaffold\\DevTools', fn(string $file): bool => $file === '.env');

    $prefix = self::drushPrefix($cwd);
    $db_file = site_db_file($extension_name);
    // The pre-warm still hits the local host:port, but the login link and the
    // completion banner report the public tunnel URL.
    $this->mockPassthruMultiple([
      ['cmd' => $prefix . 'status --field=db-status', 'output' => ''],
      ['cmd' => $prefix . sprintf('site-install %s -y --db-url=%s --account-name=admin install_configure_form.enable_update_status_module=NULL install_configure_form.enable_update_status_emails=NULL', escapeshellarg('standard'), escapeshellarg('sqlite://localhost/' . $db_file))],
      ['cmd' => $prefix . 'status'],
      ['cmd' => $prefix . 'pm:enable ' . escapeshellarg($extension_name)],
      ['cmd' => $prefix . 'cr'],
      ['cmd' => $prefix . sprintf('uli -l %s --no-browser', escapeshellarg($tunnel_url)), 'output' => $tunnel_url . '/user/reset/1/abc/login'],
    ]);

    ob_start();
    require dirname(__DIR__, 4) . '/.devtools/provision';
    $output = ob_get_clean();

    $this->assertIsString($output);
    $this->assertStringContainsString('Site URL:            ' . $tunnel_url, $output);
    $this->assertStringNotContainsString('Site URL:            http://', $output);
  }

}
