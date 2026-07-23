<?php

declare(strict_types=1);

namespace AlexSkrypnyk\drupal_extension_scaffold\Tests\Unit;

use function DrupalExtensionScaffold\DevTools\validate_port_or_fail;
use AlexSkrypnyk\drupal_extension_scaffold\Tests\Exceptions\QuitErrorException;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests for validate_port_or_fail() helper.
 *
 * phpcs:disable Drupal.Classes.FullyQualifiedNamespace.UseStatementMissing
 * phpcs:disable Drupal.Commenting.FunctionComment.Missing
 * phpcs:disable Drupal.Commenting.DocComment.MissingShort
 */
#[CoversFunction('DrupalExtensionScaffold\DevTools\validate_port_or_fail')]
#[RunTestsInSeparateProcesses]
#[Group('p0')]
final class HelpersValidatePortTest extends UnitTestCase {

  protected function setUp(): void {
    parent::setUp();
    require_once dirname(__DIR__, 4) . '/.devtools/helpers.php';
  }

  #[DataProvider('dataProviderValidPortDoesNotFail')]
  public function testValidPortDoesNotFail(string $value): void {
    validate_port_or_fail($value, 'WEBSERVER_PORT');
    $this->expectNotToPerformAssertions();
  }

  public static function dataProviderValidPortDoesNotFail(): \Iterator {
    yield 'lowest valid' => ['1'];
    yield 'common default' => ['8000'];
    yield 'highest valid' => ['65535'];
    yield 'mid range' => ['12345'];
  }

  #[DataProvider('dataProviderInvalidPortFails')]
  public function testInvalidPortFails(string $value): void {
    $this->mockQuit(1);

    $this->expectException(QuitErrorException::class);
    ob_start();
    try {
      validate_port_or_fail($value, 'WEBSERVER_PORT');
    }
    finally {
      $output = ob_get_clean();
      $this->assertIsString($output);
      $this->assertStringContainsString('Invalid WEBSERVER_PORT', $output);
      $this->assertStringContainsString('Expected integer in range 1-65535', $output);
    }
  }

  public static function dataProviderInvalidPortFails(): \Iterator {
    yield 'empty string' => [''];
    yield 'zero' => ['0'];
    yield 'too high' => ['65536'];
    yield 'negative' => ['-1'];
    yield 'non-numeric' => ['abc'];
    yield 'mixed alphanumeric' => ['80a0'];
    yield 'with shell metacharacter' => ['8000; rm -rf /'];
    yield 'with whitespace' => ['8000 '];
    yield 'decimal' => ['80.0'];
  }

  public function testUsesProvidedSourceNameInErrorMessage(): void {
    $this->mockQuit(1);

    $this->expectException(QuitErrorException::class);
    ob_start();
    try {
      validate_port_or_fail('bogus', 'CUSTOM_PORT_VAR');
    }
    finally {
      $output = ob_get_clean();
      $this->assertIsString($output);
      $this->assertStringContainsString('Invalid CUSTOM_PORT_VAR', $output);
    }
  }

}
