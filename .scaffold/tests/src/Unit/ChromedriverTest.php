<?php

declare(strict_types=1);

namespace AlexSkrypnyk\drupal_extension_scaffold\Tests\Unit;

use AlexSkrypnyk\drupal_extension_scaffold\Tests\Exceptions\QuitErrorException;
use AlexSkrypnyk\drupal_extension_scaffold\Tests\Exceptions\QuitSuccessException;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests for the .devtools/chromedriver script.
 *
 * phpcs:disable Drupal.Commenting.FunctionComment.Missing
 * phpcs:disable Drupal.Commenting.DocComment.MissingShort
 */
#[RunTestsInSeparateProcesses]
#[Group('p0')]
final class ChromedriverTest extends UnitTestCase {

  protected function setUp(): void {
    parent::setUp();
    require_once dirname(__DIR__, 4) . '/.devtools/helpers.php';
    self::envUnset('WEBDRIVER_PORT');

    // The '.env' file exists and supplies port 4444, so the port resolves
    // without touching the free-port probe. Directory creation and sleeps
    // are no-ops.
    $this->registerMock('file_exists', 'DrupalExtensionScaffold\\DevTools', fn(string $f): bool => $f === '.env');
    $this->registerMock('mkdir', 'DrupalExtensionScaffold\\DevTools', fn(): bool => TRUE);
    $this->mockSleep();
  }

  protected function tearDown(): void {
    self::envReset();
    parent::tearDown();
  }

  public function testUnknownCommand(): void {
    $this->mockFiles();

    $output = $this->runChromedriver('bogus', 1);

    $this->assertStringContainsString("Unknown command 'bogus'", $output);
  }

  public function testStartAlreadyRunning(): void {
    $this->mockFiles();
    $this->mockReady([TRUE]);

    $output = $this->runChromedriver('start', 0);

    $this->assertStringContainsString('already running on port 4444', $output);
  }

  public function testStartUsesMatchingInstalledDriver(): void {
    $this->mockFiles();
    $this->mockChrome(FALSE);
    $this->mockCommands(['google-chrome' => '/usr/bin/google-chrome', 'chromedriver' => '/usr/local/bin/chromedriver']);
    $this->mockVersions('Google Chrome 150.0.7871.129', 'ChromeDriver 150.0.7871.129 (abc)');
    $this->mockReady([FALSE, TRUE]);
    $commands = [];
    $this->recordPassthru($commands);

    $output = $this->runChromedriver('start', 0);

    $this->assertStringContainsString('Using installed chromedriver at /usr/local/bin/chromedriver', $output);
    $this->assertStringContainsString('chromedriver is ready on port 4444', $output);
    $this->assertNotEmpty($commands);
    $this->assertStringContainsString('nohup', $commands[0]);
    $this->assertStringContainsString('--port=', $commands[0]);
    foreach ($commands as $command) {
      $this->assertStringNotContainsString('npx', $command, 'A matching installed driver must not trigger an npx download.');
    }
  }

  public function testStartFallsBackToNpxOnVersionMismatch(): void {
    $this->mockFiles(log: "chromedriver@150.0.7871.129 /cache/chromedriver\n");
    $this->mockChrome(FALSE);
    $this->mockCommands(['google-chrome' => '/usr/bin/google-chrome', 'chromedriver' => '/usr/local/bin/chromedriver', 'npx' => '/usr/bin/npx']);
    $this->mockVersions('Google Chrome 150.0.7871.129', 'ChromeDriver 151.0.7922.34 (abc)');
    $this->mockReady([FALSE, TRUE]);
    $commands = [];
    $this->recordPassthru($commands);

    $output = $this->runChromedriver('start', 0);

    $this->assertStringContainsString('does not match Chrome 150.0.7871.129', $output);
    $this->assertStringContainsString('chromedriver is ready on port 4444', $output);
    $this->assertTrue((bool) array_filter($commands, fn(string $c): bool => str_contains($c, 'npx')), 'A version mismatch must trigger an npx download.');
    $this->assertTrue((bool) array_filter($commands, fn(string $c): bool => str_contains($c, 'nohup') && str_contains($c, '/cache/chromedriver')), 'The freshly fetched binary must be launched.');
  }

  public function testStartUsesMacChromeWhenPresent(): void {
    $this->mockFiles();
    $this->mockChrome(TRUE);
    $this->mockCommands(['chromedriver' => '/usr/local/bin/chromedriver']);
    $this->mockVersions('Google Chrome 150.0.7871.129', 'ChromeDriver 150.0.7871.129 (abc)');
    $this->mockReady([FALSE, TRUE]);
    $commands = [];
    $this->recordPassthru($commands);

    $output = $this->runChromedriver('start', 0);

    $this->assertStringContainsString('chromedriver is ready on port 4444', $output);
  }

  public function testStartFailsWhenChromeMissing(): void {
    $this->mockFiles();
    $this->mockChrome(FALSE);
    $this->mockCommands([]);
    $this->mockVersions('', '');
    $this->mockReady([FALSE]);

    $output = $this->runChromedriver('start', 1);

    $this->assertStringContainsString('Google Chrome or Chromium was not found', $output);
  }

  public function testStartFailsWhenMismatchAndNoNpx(): void {
    $this->mockFiles();
    $this->mockChrome(FALSE);
    $this->mockCommands(['google-chrome' => '/usr/bin/google-chrome', 'chromedriver' => '/usr/local/bin/chromedriver']);
    $this->mockVersions('Google Chrome 150.0.7871.129', 'ChromeDriver 151.0.7922.34 (abc)');
    $this->mockReady([FALSE]);

    $output = $this->runChromedriver('start', 1);

    $this->assertStringContainsString('npx is unavailable', $output);
  }

  public function testStartFailsWhenNpxInstallFails(): void {
    $this->mockFiles();
    $this->mockChrome(FALSE);
    $this->mockCommands(['google-chrome' => '/usr/bin/google-chrome', 'npx' => '/usr/bin/npx']);
    $this->mockVersions('Google Chrome 150.0.7871.129', '');
    $this->mockReady([FALSE]);
    $commands = [];
    $this->recordPassthru($commands, npx_exit: 1);

    $output = $this->runChromedriver('start', 1);

    $this->assertStringContainsString('Failed to install chromedriver', $output);
  }

  public function testStartFailsWhenBinaryPathUnresolved(): void {
    $this->mockFiles(log: '');
    $this->mockChrome(FALSE);
    $this->mockCommands(['google-chrome' => '/usr/bin/google-chrome', 'npx' => '/usr/bin/npx']);
    $this->mockVersions('Google Chrome 150.0.7871.129', '');
    $this->mockReady([FALSE]);
    $commands = [];
    $this->recordPassthru($commands);

    $output = $this->runChromedriver('start', 1);

    $this->assertStringContainsString('Could not determine the chromedriver binary path', $output);
  }

  public function testStartFailsWhenNeverReady(): void {
    $this->mockFiles();
    $this->mockChrome(FALSE);
    $this->mockCommands(['google-chrome' => '/usr/bin/google-chrome', 'chromedriver' => '/usr/local/bin/chromedriver']);
    $this->mockVersions('Google Chrome 150.0.7871.129', 'ChromeDriver 150.0.7871.129 (abc)');
    $this->mockReady([FALSE]);
    $commands = [];
    $this->recordPassthru($commands);

    $output = $this->runChromedriver('start', 1);

    $this->assertStringContainsString('failed to become ready on port 4444', $output);
  }

  public function testStop(): void {
    $this->mockFiles();
    $commands = [];
    $this->recordPassthru($commands);

    $output = $this->runChromedriver('stop', 0);

    $this->assertStringContainsString('chromedriver stopped on port 4444', $output);
    $this->assertNotEmpty($commands);
    $this->assertStringContainsString("lsof -ti:'4444'", $commands[0]);
  }

  // ---------------------------------------------------------------------------
  // Helpers.
  // ---------------------------------------------------------------------------

  protected function runChromedriver(string $subcommand, int $expected_exit): string {
    $this->mockQuit($expected_exit);

    // The included script inherits this scope, so it reads $argv from here.
    $argv = ['chromedriver', $subcommand];

    ob_start();
    try {
      require dirname(__DIR__, 4) . '/.devtools/chromedriver';
      $this->fail('Expected chromedriver to call quit().');
    }
    catch (QuitSuccessException | QuitErrorException $e) {
      $this->assertSame($expected_exit, $e->getCode());
    }
    finally {
      $output = (string) ob_get_clean();
    }

    return $output;
  }

  protected function mockFiles(string $env = "WEBDRIVER_PORT=4444\n", string $log = "chromedriver@150.0.7871.129 /cache/chromedriver\n"): void {
    $this->registerMock('file_get_contents', 'DrupalExtensionScaffold\\DevTools', function (string $f) use ($env, $log): string|false {
      if ($f === '.env') {
        return $env;
      }
      if (str_contains($f, 'install.log')) {
        return $log === '' ? FALSE : $log;
      }

      return FALSE;
    });
  }

  protected function mockChrome(bool $mac_present): void {
    $this->registerMock('is_executable', 'DrupalExtensionScaffold\\DevTools', fn(): bool => $mac_present);
  }

  /**
   * @param array<string, string> $map
   *   Command name => resolved path for commands that are present.
   */
  protected function mockCommands(array $map): void {
    $this->registerMock('exec', 'DrupalExtensionScaffold\\DevTools', function (string $cmd, &$output = NULL, &$result_code = NULL) use ($map): string {
      $output = [];
      $result_code = 1;
      if (preg_match('/command -v (\S+)/', $cmd, $matches) === 1 && isset($map[$matches[1]])) {
        $output = [$map[$matches[1]]];
        $result_code = 0;
      }

      return '';
    });
  }

  protected function mockVersions(string $chrome, string $driver): void {
    $this->registerMock('shell_exec', 'DrupalExtensionScaffold\\DevTools', function (string $cmd) use ($chrome, $driver): string {
      if (str_contains($cmd, 'chromedriver')) {
        return $driver;
      }
      if (str_contains($cmd, 'hrome') || str_contains($cmd, 'hromium')) {
        return $chrome;
      }

      return '';
    });
  }

  /**
   * @param array<int, bool> $sequence
   *   Readiness result per call; the last value repeats for further calls.
   */
  protected function mockReady(array $sequence): void {
    $index = 0;
    $this->registerMock('get_headers', 'DrupalExtensionScaffold\\DevTools', function () use (&$index, $sequence): array|false {
      $ready = $sequence[$index] ?? end($sequence);
      $index++;

      return $ready ? ['HTTP/1.1 200 OK'] : FALSE;
    });
  }

  /**
   * @param array<int, string> $commands
   *   Populated by reference with every passthru() command line.
   */
  protected function recordPassthru(array &$commands, int $npx_exit = 0): void {
    $this->registerMock('passthru', 'DrupalExtensionScaffold\\DevTools', function (string $cmd, &$result_code = NULL) use (&$commands, $npx_exit): void {
      $commands[] = $cmd;
      if (str_contains($cmd, 'npx')) {
        $result_code = $npx_exit;
      }
    });
  }

}
