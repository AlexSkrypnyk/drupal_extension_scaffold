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
      ['cmd' => sprintf('nohup php -S %s:%s -t %s/build/web %s/build/web/.ht.router.php >/tmp/php.log 2>&1 &', escapeshellarg($expected_host), escapeshellarg($expected_port), escapeshellarg($cwd), escapeshellarg($cwd))],
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
    yield 'explicit default port' => [
      'env' => ['WEBSERVER_PORT' => '8000'],
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
      'env' => ['WEBSERVER_PORT' => '8000', 'WEBSERVER_WAIT_TIMEOUT' => '10'],
      'expected_host' => 'localhost',
      'expected_port' => '8000',
      'expected_timeout' => 10,
    ];
  }

  public function testStartServerWith302Response(): void {
    $this->envSet('WEBSERVER_PORT', '8000');
    $cwd = '/test/project';

    $this->registerMock('getcwd', 'DrupalExtensionScaffold\\DevTools', fn(): string => $cwd);

    $this->mockPassthruMultiple([
      ['cmd' => "lsof -ti:'8000' | xargs kill -9 2>/dev/null"],
      ['cmd' => sprintf('nohup php -S %s:%s -t %s/build/web %s/build/web/.ht.router.php >/tmp/php.log 2>&1 &', escapeshellarg('localhost'), escapeshellarg('8000'), escapeshellarg($cwd), escapeshellarg($cwd))],
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
    $this->envSet('WEBSERVER_PORT', '8000');
    $cwd = '/test/project';

    $this->registerMock('getcwd', 'DrupalExtensionScaffold\\DevTools', fn(): string => $cwd);

    $this->mockPassthruMultiple([
      ['cmd' => "lsof -ti:'8000' | xargs kill -9 2>/dev/null"],
      ['cmd' => sprintf('nohup php -S %s:%s -t %s/build/web %s/build/web/.ht.router.php >/tmp/php.log 2>&1 &', escapeshellarg('localhost'), escapeshellarg('8000'), escapeshellarg($cwd), escapeshellarg($cwd))],
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
      $this->assertSame(1, $e->getCode());
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
    $this->envSet('WEBSERVER_PORT', '8000');
    $cwd = '/test/project';

    $this->registerMock('getcwd', 'DrupalExtensionScaffold\\DevTools', fn(): string => $cwd);

    $this->mockPassthruMultiple([
      ['cmd' => "lsof -ti:'8000' | xargs kill -9 2>/dev/null"],
      ['cmd' => sprintf('nohup php -S %s:%s -t %s/build/web %s/build/web/.ht.router.php >/tmp/php.log 2>&1 &', escapeshellarg('localhost'), escapeshellarg('8000'), escapeshellarg($cwd), escapeshellarg($cwd))],
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
      $this->assertSame(1, $e->getCode());
    }
    finally {
      $output = ob_get_clean();
      $this->assertIsString($output);
      $this->assertStringContainsString('Unable to start inbuilt PHP server', $output);
    }
  }

  public function testStartGetHeadersFailure(): void {
    $this->envSet('WEBSERVER_PORT', '8000');
    $cwd = '/test/project';

    $this->registerMock('getcwd', 'DrupalExtensionScaffold\\DevTools', fn(): string => $cwd);

    $this->mockPassthruMultiple([
      ['cmd' => "lsof -ti:'8000' | xargs kill -9 2>/dev/null"],
      ['cmd' => sprintf('nohup php -S %s:%s -t %s/build/web %s/build/web/.ht.router.php >/tmp/php.log 2>&1 &', escapeshellarg('localhost'), escapeshellarg('8000'), escapeshellarg($cwd), escapeshellarg($cwd))],
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
      $this->assertSame(1, $e->getCode());
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
    $this->envSet('WEBSERVER_PORT', '8000');
    $cwd = '/test/project';

    $this->registerMock('getcwd', 'DrupalExtensionScaffold\\DevTools', fn(): string => $cwd);

    $this->mockPassthruMultiple([
      ['cmd' => "lsof -ti:'8000' | xargs kill -9 2>/dev/null"],
      ['cmd' => sprintf('nohup php -S %s:%s -t %s/build/web %s/build/web/.ht.router.php >/tmp/php.log 2>&1 &', escapeshellarg('localhost'), escapeshellarg('8000'), escapeshellarg($cwd), escapeshellarg($cwd))],
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
      $this->assertSame(1, $e->getCode());
    }
    finally {
      $output = ob_get_clean();
      $this->assertIsString($output);
      $this->assertStringContainsString('Server is started, but site cannot be served', $output);
    }

    fclose($fp);
  }

  public function testStartReadsPortFromDotenvAndDoesNotRewrite(): void {
    $cwd = '/test/project';

    $this->registerMock('getcwd', 'DrupalExtensionScaffold\\DevTools', fn(): string => $cwd);

    // .env file exists with WEBSERVER_PORT=8123.
    $this->registerMock('file_exists', 'DrupalExtensionScaffold\\DevTools', fn(string $file): bool => $file === '.env');
    $this->registerMock('file_get_contents', 'DrupalExtensionScaffold\\DevTools', fn(): string => "WEBSERVER_PORT=8123\n");

    // file_put_contents must not be called - we read from .env, do not rewrite.
    $put_called = FALSE;
    $this->registerMock('file_put_contents', 'DrupalExtensionScaffold\\DevTools', function () use (&$put_called): false {
      $put_called = TRUE;

      return FALSE;
    });

    // stream_socket_server must not be called - we read from .env, do not
    // discover.
    $stream_called = FALSE;
    $this->registerMock('stream_socket_server', 'DrupalExtensionScaffold\\DevTools', function () use (&$stream_called): false {
      $stream_called = TRUE;

      return FALSE;
    });

    $this->mockPassthruMultiple([
      ['cmd' => "lsof -ti:'8123' | xargs kill -9 2>/dev/null"],
      ['cmd' => sprintf('nohup php -S %s:%s -t %s/build/web %s/build/web/.ht.router.php >/tmp/php.log 2>&1 &', escapeshellarg('localhost'), escapeshellarg('8123'), escapeshellarg($cwd), escapeshellarg($cwd))],
    ]);

    $this->mockSleep();

    $fp = fopen('php://memory', 'r');
    $this->assertNotFalse($fp);
    $this->registerMock('fsockopen', 'DrupalExtensionScaffold\\DevTools', fn() => $fp);
    $this->registerMock('fclose', 'DrupalExtensionScaffold\\DevTools', fn(): true => TRUE);

    $context = stream_context_create();
    $this->registerMock('stream_context_create', 'DrupalExtensionScaffold\\DevTools', fn() => $context);
    $this->registerMock('get_headers', 'DrupalExtensionScaffold\\DevTools', fn(): array => ['HTTP/1.1 200 OK']);

    ob_start();
    require dirname(__DIR__, 4) . '/.devtools/start';
    $output = ob_get_clean();

    $this->assertIsString($output);
    $this->assertStringContainsString('http://localhost:8123', $output);
    $this->assertFalse($put_called, 'file_put_contents should not be called when .env already has WEBSERVER_PORT.');
    $this->assertFalse($stream_called, 'stream_socket_server should not be called when .env already has WEBSERVER_PORT.');

    fclose($fp);
  }

  public function testStartAutoDiscoversPortAndPersistsToDotenv(): void {
    $cwd = '/test/project';

    $this->registerMock('getcwd', 'DrupalExtensionScaffold\\DevTools', fn(): string => $cwd);

    // .env file does not exist.
    $this->registerMock('file_exists', 'DrupalExtensionScaffold\\DevTools', fn(): bool => FALSE);

    // First port (8000) busy, second port (8001) free.
    // find_free_port uses stream_socket_client probe. Connect to 8000
    // succeeds (port in use); connect to 8001 refused (port free).
    $port_attempts = 0;
    $this->registerMock('stream_socket_client', 'DrupalExtensionScaffold\\DevTools', function (string $address) use (&$port_attempts) {
      $port_attempts++;
      if (str_contains($address, ':8000')) {
        return fopen('php://memory', 'r');
      }

      return FALSE;
    });

    // file_put_contents must be called to persist the discovered port.
    $persisted_port = NULL;
    $persisted_file = NULL;
    $this->registerMock('file_put_contents', 'DrupalExtensionScaffold\\DevTools', function (string $file, string $contents) use (&$persisted_port, &$persisted_file): int {
      $persisted_file = $file;
      if (preg_match('/WEBSERVER_PORT=(\d+)/', $contents, $matches)) {
        $persisted_port = $matches[1];
      }

      return strlen($contents);
    });

    $this->mockPassthruMultiple([
      ['cmd' => "lsof -ti:'8001' | xargs kill -9 2>/dev/null"],
      ['cmd' => sprintf('nohup php -S %s:%s -t %s/build/web %s/build/web/.ht.router.php >/tmp/php.log 2>&1 &', escapeshellarg('localhost'), escapeshellarg('8001'), escapeshellarg($cwd), escapeshellarg($cwd))],
    ]);

    $this->mockSleep();

    $fp = fopen('php://memory', 'r');
    $this->assertNotFalse($fp);
    $this->registerMock('fsockopen', 'DrupalExtensionScaffold\\DevTools', fn() => $fp);
    $this->registerMock('fclose', 'DrupalExtensionScaffold\\DevTools', fn(): true => TRUE);

    $context = stream_context_create();
    $this->registerMock('stream_context_create', 'DrupalExtensionScaffold\\DevTools', fn() => $context);
    $this->registerMock('get_headers', 'DrupalExtensionScaffold\\DevTools', fn(): array => ['HTTP/1.1 200 OK']);

    ob_start();
    require dirname(__DIR__, 4) . '/.devtools/start';
    $output = ob_get_clean();

    $this->assertIsString($output);
    $this->assertStringContainsString('http://localhost:8001', $output);
    $this->assertSame('.env', $persisted_file);
    $this->assertSame('8001', $persisted_port);
    // 2 probe calls: localhost:8000 (in use), localhost:8001 (free).
    $this->assertSame(2, $port_attempts);

    fclose($fp);
  }

  public function testStartDisplaysTunnelUrlWhenSet(): void {
    $cwd = '/test/project';
    $tunnel_url = 'https://random-words.trycloudflare.com';

    $this->registerMock('getcwd', 'DrupalExtensionScaffold\\DevTools', fn(): string => $cwd);

    // .env carries both the port and an active tunnel URL. The server still
    // binds and is health-checked on the local host:port, but the READY
    // banner reports the public tunnel URL.
    $this->registerMock('file_exists', 'DrupalExtensionScaffold\\DevTools', fn(string $file): bool => $file === '.env');
    $this->registerMock('file_get_contents', 'DrupalExtensionScaffold\\DevTools', fn(): string => "WEBSERVER_PORT=8000\nTUNNEL_URL=" . $tunnel_url . "\n");

    $this->mockPassthruMultiple([
      ['cmd' => "lsof -ti:'8000' | xargs kill -9 2>/dev/null"],
      ['cmd' => sprintf('nohup php -S %s:%s -t %s/build/web %s/build/web/.ht.router.php >/tmp/php.log 2>&1 &', escapeshellarg('localhost'), escapeshellarg('8000'), escapeshellarg($cwd), escapeshellarg($cwd))],
    ]);

    $this->mockSleep();

    $fp = fopen('php://memory', 'r');
    $this->assertNotFalse($fp);
    $this->registerMock('fsockopen', 'DrupalExtensionScaffold\\DevTools', fn() => $fp);
    $this->registerMock('fclose', 'DrupalExtensionScaffold\\DevTools', fn(): true => TRUE);

    $context = stream_context_create();
    $this->registerMock('stream_context_create', 'DrupalExtensionScaffold\\DevTools', fn() => $context);
    $this->registerMock('get_headers', 'DrupalExtensionScaffold\\DevTools', fn(): array => ['HTTP/1.1 200 OK']);

    ob_start();
    require dirname(__DIR__, 4) . '/.devtools/start';
    $output = ob_get_clean();

    $this->assertIsString($output);
    $this->assertStringContainsString('URL       : ' . $tunnel_url, $output);
    $this->assertStringNotContainsString('URL       : http://localhost:8000', $output);

    fclose($fp);
  }

  public function testStartGetHeadersEmptyArray(): void {
    $this->envSet('WEBSERVER_PORT', '8000');
    $cwd = '/test/project';

    $this->registerMock('getcwd', 'DrupalExtensionScaffold\\DevTools', fn(): string => $cwd);

    $this->mockPassthruMultiple([
      ['cmd' => "lsof -ti:'8000' | xargs kill -9 2>/dev/null"],
      ['cmd' => sprintf('nohup php -S %s:%s -t %s/build/web %s/build/web/.ht.router.php >/tmp/php.log 2>&1 &', escapeshellarg('localhost'), escapeshellarg('8000'), escapeshellarg($cwd), escapeshellarg($cwd))],
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
      $this->assertSame(1, $e->getCode());
    }
    finally {
      $output = ob_get_clean();
      $this->assertIsString($output);
      $this->assertStringContainsString('Server is started, but site cannot be served', $output);
    }

    fclose($fp);
  }

}
