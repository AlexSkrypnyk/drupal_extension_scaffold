<?php

declare(strict_types=1);

namespace AlexSkrypnyk\drupal_extension_scaffold\Tests\Unit;

use function DrupalExtensionScaffold\DevTools\passthru_verbose_or_fail;
use AlexSkrypnyk\drupal_extension_scaffold\Tests\Exceptions\QuitErrorException;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\Group;

#[CoversFunction('DrupalExtensionScaffold\DevTools\passthru_verbose_or_fail')]
#[Group('p0')]
final class HelpersPassthruVerboseOrFailTest extends UnitTestCase {

  protected function setUp(): void {
    parent::setUp();
    require_once dirname(__DIR__, 4) . '/.devtools/helpers.php';
  }

  public function testShowsOutputOnSuccess(): void {
    $this->mockPassthru(['cmd' => 'echo hello', 'output' => 'BUILD_OUTPUT', 'result_code' => 0]);
    ob_start();
    passthru_verbose_or_fail('echo hello');
    $output = ob_get_clean();
    $this->assertIsString($output);
    $this->assertStringContainsString('BUILD_OUTPUT', $output);
  }

  public function testFailureNoMessage(): void {
    $this->mockPassthru(['cmd' => 'false', 'result_code' => 42]);
    $this->mockQuit(42);
    $this->expectException(QuitErrorException::class);
    $this->expectExceptionCode(42);
    passthru_verbose_or_fail('false');
  }

  public function testFailureWithMessage(): void {
    $this->mockPassthru(['cmd' => 'false', 'result_code' => 42]);
    $this->mockQuit(42);
    ob_start();
    try {
      passthru_verbose_or_fail('false', 'Command failed.');
      $this->fail('Expected QuitErrorException to be thrown');
    }
    catch (QuitErrorException $e) {
      $this->assertSame(42, $e->getCode());
    }
    finally {
      $output = ob_get_clean();
      $this->assertIsString($output);
      $this->assertStringContainsString('Command failed.', $output);
    }
  }

  public function testFailureWithFormatArgs(): void {
    $this->mockPassthru(['cmd' => 'curl http://example.com', 'result_code' => 7]);
    $this->mockQuit(7);
    ob_start();
    try {
      passthru_verbose_or_fail('curl http://example.com', 'Failed to download from %s.', 'http://example.com');
      $this->fail('Expected QuitErrorException to be thrown');
    }
    catch (QuitErrorException $e) {
      $this->assertSame(7, $e->getCode());
    }
    finally {
      $output = ob_get_clean();
      $this->assertIsString($output);
      $this->assertStringContainsString('Failed to download from http://example.com.', $output);
    }
  }

}
