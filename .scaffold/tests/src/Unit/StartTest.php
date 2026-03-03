<?php

declare(strict_types=1);

namespace AlexSkrypnyk\drupal_extension_scaffold\Tests\Unit;

use AlexSkrypnyk\drupal_extension_scaffold\Tests\Exceptions\QuitErrorException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests for the start devtools script.
 *
 * phpcs:disable Drupal.Commenting.FunctionComment.Missing
 * phpcs:disable Drupal.Commenting.DocComment.MissingShort
 */
#[RunTestsInSeparateProcesses]
#[Group('p0')]
final class StartTest extends UnitTestCase {

  protected function setUp(): void {
    parent::setUp();
    require_once dirname(__DIR__, 4) . '/.devtools/helpers.php';
  }

  #[DataProvider('dataProviderStartSuccess')]
  public function testStartSuccess(array $env, string $expected_host, string $expected_port, int $expected_timeout): void {
    foreach ($env as $name => $value) {
      $this->envSet($name, $value);
    }

    $cwd = '/test/project';

    // Mock getcwd().
    $this->registerMock('getcwd', 'DrupalExtensionScaffold\\DevTools', fn(): string => $cwd);

    // Mock passthru: 1) kill existing, 2) start server.
    $this->mockPassthruMultiple([
      ['cmd' => sprintf("lsof -ti:%s | xargs kill -9 2>/dev/null", escapeshellarg($expected_port))],
      ['cmd' => sprintf('nohup php -S %s:%s -t %s/build/web %s/build/web/.ht.router.php >/tmp/php.log 2>&1 &', $expected_host, $expected_port, $cwd, $cwd)],
    ]);

    $this->mockSleep();

    // Mock fsockopen() - success.
    $fp = fopen('php://memory', 'r');
    $this->assertNotFalse($fp);
    $this->registerMock('fsockopen', 'DrupalExtensionScaffold\\DevTools', fn() => $fp);

    // Mock fclose().
    $this->registerMock('fclose', 'DrupalExtensionScaffold\\DevTools', fn(): true => TRUE);

    // Mock stream_context_create().
    $context = stream_context_create();
    $this->registerMock('stream_context_create', 'DrupalExtensionScaffold\\DevTools', fn() => $context);

    // Mock get_headers() - success with 200.
    $this->registerMock('get_headers', 'DrupalExtensionScaffold\\DevTools', fn(): array => ['HTTP/1.1 200 OK']);

    ob_start();
    require dirname(__DIR__, 4) . '/.devtools/start';
    $output = ob_get_clean();

    $this->assertIsString($output);
    $this->assertStringContainsString('START ENVIRONMENT', $output);
    $this->assertStringContainsString('Server started successfully', $output);
    $this->assertStringContainsString('Server can serve content', $output);
    $this->assertStringContainsString('ENVIRONMENT READY', $output);
    $this->assertStringContainsString($cwd . '/build/web', $output);
    $this->assertStringContainsString('http://' . $expected_host . ':' . $expected_port, $output);

    fclose($fp);
  }

  public static function dataProviderStartSuccess(): \Iterator {
    yield 'default config' => [
      'env' => [],
      'expected_host' => 'localhost',
      'expected_port' => '8000',
      'expected_timeout' => 5,
    ];
    yield 'custom host and port' => [
      'env' => ['WEBSERVER_HOST' => '0.0.0.0', 'WEBSERVER_PORT' => '9000'],
      'expected_host' => '0.0.0.0',
      'expected_port' => '9000',
      'expected_timeout' => 5,
    ];
    yield 'custom timeout' => [
      'env' => ['WEBSERVER_WAIT_TIMEOUT' => '10'],
      'expected_host' => 'localhost',
      'expected_port' => '8000',
      'expected_timeout' => 10,
    ];
  }

  public function testStartServerWith302Response(): void {
    $cwd = '/test/project';

    $this->registerMock('getcwd', 'DrupalExtensionScaffold\\DevTools', fn(): string => $cwd);

    $this->mockPassthruMultiple([
      ['cmd' => "lsof -ti:'8000' | xargs kill -9 2>/dev/null"],
      ['cmd' => sprintf('nohup php -S localhost:8000 -t %s/build/web %s/build/web/.ht.router.php >/tmp/php.log 2>&1 &', $cwd, $cwd)],
    ]);

    $this->mockSleep();

    $fp = fopen('php://memory', 'r');
    $this->assertNotFalse($fp);
    $this->registerMock('fsockopen', 'DrupalExtensionScaffold\\DevTools', fn() => $fp);
    $this->registerMock('fclose', 'DrupalExtensionScaffold\\DevTools', fn(): true => TRUE);

    $context = stream_context_create();
    $this->registerMock('stream_context_create', 'DrupalExtensionScaffold\\DevTools', fn() => $context);

    // Return 302 redirect - should still pass.
    $this->registerMock('get_headers', 'DrupalExtensionScaffold\\DevTools', fn(): array => ['HTTP/1.1 302 Found']);

    ob_start();
    require dirname(__DIR__, 4) . '/.devtools/start';
    $output = ob_get_clean();

    $this->assertIsString($output);
    $this->assertStringContainsString('ENVIRONMENT READY', $output);
    $this->assertStringContainsString('Server can serve content', $output);

    fclose($fp);
  }

  public function testStartFsockopenFailure(): void {
    $cwd = '/test/project';

    $this->registerMock('getcwd', 'DrupalExtensionScaffold\\DevTools', fn(): string => $cwd);

    $this->mockPassthruMultiple([
      ['cmd' => "lsof -ti:'8000' | xargs kill -9 2>/dev/null"],
      ['cmd' => sprintf('nohup php -S localhost:8000 -t %s/build/web %s/build/web/.ht.router.php >/tmp/php.log 2>&1 &', $cwd, $cwd)],
    ]);

    $this->mockSleep();

    // Mock fsockopen() - failure.
    $this->registerMock('fsockopen', 'DrupalExtensionScaffold\\DevTools', fn(): false => FALSE);

    // Mock file_get_contents for the error log.
    $this->registerMock('file_get_contents', 'DrupalExtensionScaffold\\DevTools', fn(): string => 'PHP Fatal error: some error');

    $this->mockQuit(1);

    ob_start();
    try {
      require dirname(__DIR__, 4) . '/.devtools/start';
      $this->fail('Expected QuitErrorException to be thrown');
    }
    catch (QuitErrorException $e) {
      $this->assertEquals(1, $e->getCode());
    }
    finally {
      $output = ob_get_clean();
      $this->assertIsString($output);
      $this->assertStringContainsString('START ENVIRONMENT', $output);
      $this->assertStringContainsString('Unable to start inbuilt PHP server', $output);
      $this->assertStringContainsString('PHP Fatal error: some error', $output);
    }
  }

  public function testStartFsockopenFailureNoLog(): void {
    $cwd = '/test/project';

    $this->registerMock('getcwd', 'DrupalExtensionScaffold\\DevTools', fn(): string => $cwd);

    $this->mockPassthruMultiple([
      ['cmd' => "lsof -ti:'8000' | xargs kill -9 2>/dev/null"],
      ['cmd' => sprintf('nohup php -S localhost:8000 -t %s/build/web %s/build/web/.ht.router.php >/tmp/php.log 2>&1 &', $cwd, $cwd)],
    ]);

    $this->mockSleep();

    $this->registerMock('fsockopen', 'DrupalExtensionScaffold\\DevTools', fn(): false => FALSE);

    // No log file content.
    $this->registerMock('file_get_contents', 'DrupalExtensionScaffold\\DevTools', fn(): false => FALSE);

    $this->mockQuit(1);

    ob_start();
    try {
      require dirname(__DIR__, 4) . '/.devtools/start';
      $this->fail('Expected QuitErrorException to be thrown');
    }
    catch (QuitErrorException $e) {
      $this->assertEquals(1, $e->getCode());
    }
    finally {
      $output = ob_get_clean();
      $this->assertIsString($output);
      $this->assertStringContainsString('Unable to start inbuilt PHP server', $output);
    }
  }

  public function testStartGetHeadersFailure(): void {
    $cwd = '/test/project';

    $this->registerMock('getcwd', 'DrupalExtensionScaffold\\DevTools', fn(): string => $cwd);

    $this->mockPassthruMultiple([
      ['cmd' => "lsof -ti:'8000' | xargs kill -9 2>/dev/null"],
      ['cmd' => sprintf('nohup php -S localhost:8000 -t %s/build/web %s/build/web/.ht.router.php >/tmp/php.log 2>&1 &', $cwd, $cwd)],
    ]);

    $this->mockSleep();

    $fp = fopen('php://memory', 'r');
    $this->assertNotFalse($fp);
    $this->registerMock('fsockopen', 'DrupalExtensionScaffold\\DevTools', fn() => $fp);
    $this->registerMock('fclose', 'DrupalExtensionScaffold\\DevTools', fn(): true => TRUE);

    $context = stream_context_create();
    $this->registerMock('stream_context_create', 'DrupalExtensionScaffold\\DevTools', fn() => $context);

    // get_headers returns FALSE.
    $this->registerMock('get_headers', 'DrupalExtensionScaffold\\DevTools', fn(): false => FALSE);

    $this->mockQuit(1);

    ob_start();
    try {
      require dirname(__DIR__, 4) . '/.devtools/start';
      $this->fail('Expected QuitErrorException to be thrown');
    }
    catch (QuitErrorException $e) {
      $this->assertEquals(1, $e->getCode());
    }
    finally {
      $output = ob_get_clean();
      $this->assertIsString($output);
      $this->assertStringContainsString('Server started successfully', $output);
      $this->assertStringContainsString('Server is started, but site cannot be served', $output);
    }

    fclose($fp);
  }

  public function testStartGetHeadersNon200Non302(): void {
    $cwd = '/test/project';

    $this->registerMock('getcwd', 'DrupalExtensionScaffold\\DevTools', fn(): string => $cwd);

    $this->mockPassthruMultiple([
      ['cmd' => "lsof -ti:'8000' | xargs kill -9 2>/dev/null"],
      ['cmd' => sprintf('nohup php -S localhost:8000 -t %s/build/web %s/build/web/.ht.router.php >/tmp/php.log 2>&1 &', $cwd, $cwd)],
    ]);

    $this->mockSleep();

    $fp = fopen('php://memory', 'r');
    $this->assertNotFalse($fp);
    $this->registerMock('fsockopen', 'DrupalExtensionScaffold\\DevTools', fn() => $fp);
    $this->registerMock('fclose', 'DrupalExtensionScaffold\\DevTools', fn(): true => TRUE);

    $context = stream_context_create();
    $this->registerMock('stream_context_create', 'DrupalExtensionScaffold\\DevTools', fn() => $context);

    // Return 500 - should fail.
    $this->registerMock('get_headers', 'DrupalExtensionScaffold\\DevTools', fn(): array => ['HTTP/1.1 500 Internal Server Error']);

    $this->mockQuit(1);

    ob_start();
    try {
      require dirname(__DIR__, 4) . '/.devtools/start';
      $this->fail('Expected QuitErrorException to be thrown');
    }
    catch (QuitErrorException $e) {
      $this->assertEquals(1, $e->getCode());
    }
    finally {
      $output = ob_get_clean();
      $this->assertIsString($output);
      $this->assertStringContainsString('Server is started, but site cannot be served', $output);
    }

    fclose($fp);
  }

  public function testStartGetHeadersEmptyArray(): void {
    $cwd = '/test/project';

    $this->registerMock('getcwd', 'DrupalExtensionScaffold\\DevTools', fn(): string => $cwd);

    $this->mockPassthruMultiple([
      ['cmd' => "lsof -ti:'8000' | xargs kill -9 2>/dev/null"],
      ['cmd' => sprintf('nohup php -S localhost:8000 -t %s/build/web %s/build/web/.ht.router.php >/tmp/php.log 2>&1 &', $cwd, $cwd)],
    ]);

    $this->mockSleep();

    $fp = fopen('php://memory', 'r');
    $this->assertNotFalse($fp);
    $this->registerMock('fsockopen', 'DrupalExtensionScaffold\\DevTools', fn() => $fp);
    $this->registerMock('fclose', 'DrupalExtensionScaffold\\DevTools', fn(): true => TRUE);

    $context = stream_context_create();
    $this->registerMock('stream_context_create', 'DrupalExtensionScaffold\\DevTools', fn() => $context);

    // Empty headers array - no [0] element.
    $this->registerMock('get_headers', 'DrupalExtensionScaffold\\DevTools', fn(): array => []);

    $this->mockQuit(1);

    ob_start();
    try {
      require dirname(__DIR__, 4) . '/.devtools/start';
      $this->fail('Expected QuitErrorException to be thrown');
    }
    catch (QuitErrorException $e) {
      $this->assertEquals(1, $e->getCode());
    }
    finally {
      $output = ob_get_clean();
      $this->assertIsString($output);
      $this->assertStringContainsString('Server is started, but site cannot be served', $output);
    }

    fclose($fp);
  }

}
