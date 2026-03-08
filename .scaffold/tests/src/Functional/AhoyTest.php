<?php

declare(strict_types=1);

namespace AlexSkrypnyk\drupal_extension_scaffold\Tests\Functional;

use AlexSkrypnyk\File\File;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests for the ahoy-based devtools workflow.
 *
 * phpcs:disable Drupal.Commenting.FunctionComment.Missing
 * phpcs:disable Drupal.Commenting.DocComment.MissingShort
 */
#[Group('p3')]
final class AhoyTest extends DevtoolsTestCase {

  public function testBuild(): void {
    $this->processRun('ahoy', ['build'], [], [], $this->longTimeout, $this->defaultIdleTimeout);
    $this->assertProcessSuccessful();
    $this->assertProcessAnyOutputContains('PROVISION COMPLETE');
  }

  public function testAssemble(): void {
    $this->processRun('ahoy', ['assemble'], [], [], $this->longTimeout, $this->defaultIdleTimeout);
    $this->assertProcessSuccessful();

    $this->assertProcessAnyOutputContains('ASSEMBLE COMPLETE');
    $this->assertDirectoryExists(self::$sut . '/build/vendor');
    $this->assertFileExists(self::$sut . '/build/composer.json');
    $this->assertFileExists(self::$sut . '/build/composer.lock');
    $this->assertDirectoryExists(self::$sut . '/build/node_modules');
    $this->assertProcessAnyOutputContains('Would run build');
  }

  public function testAssembleSkipNpmBuild(): void {
    touch(self::$sut . '/.skip_npm_build');

    $this->processRun('ahoy', ['assemble'], [], [], $this->longTimeout, $this->defaultIdleTimeout);
    $this->assertProcessSuccessful();

    $this->assertProcessAnyOutputContains('ASSEMBLE COMPLETE');
    $this->assertDirectoryExists(self::$sut . '/build/vendor');
    $this->assertFileExists(self::$sut . '/build/composer.json');
    $this->assertFileExists(self::$sut . '/build/composer.lock');
    $this->assertDirectoryDoesNotExist(self::$sut . '/build/node_modules');
    $this->assertProcessAnyOutputNotContains('Would run build');
  }

  public function testStart(): void {
    $this->processRun('ahoy', ['start'], [], [], $this->defaultTimeout, $this->defaultIdleTimeout);
    $this->assertProcessFailed();
    $this->assertProcessAnyOutputNotContains('ENVIRONMENT READY');

    $this->processRun('ahoy', ['assemble'], [], [], $this->longTimeout, $this->defaultIdleTimeout);
    $this->assertProcessSuccessful();

    $this->processRun('ahoy', ['start'], [], [], $this->defaultTimeout, $this->defaultIdleTimeout);
    $this->assertProcessSuccessful();
    $this->assertProcessAnyOutputContains('ENVIRONMENT READY');
  }

  public function testStop(): void {
    $this->processRun('ahoy', ['stop'], [], [], $this->defaultTimeout, $this->defaultIdleTimeout);
    $this->assertProcessSuccessful();
    $this->assertProcessAnyOutputContains('ENVIRONMENT STOPPED');

    $this->processRun('ahoy', ['assemble'], [], [], $this->longTimeout, $this->defaultIdleTimeout);
    $this->assertProcessSuccessful();

    $this->processRun('ahoy', ['start'], [], [], $this->defaultTimeout, $this->defaultIdleTimeout);
    $this->assertProcessSuccessful();

    $this->processRun('ahoy', ['stop'], [], [], $this->defaultTimeout, $this->defaultIdleTimeout);
    $this->assertProcessSuccessful();
    $this->assertProcessAnyOutputContains('ENVIRONMENT STOPPED');
  }

  public function testProvision(): void {
    $this->processRun('ahoy', ['assemble'], [], [], $this->longTimeout, $this->defaultIdleTimeout);
    $this->assertProcessSuccessful();

    $this->processRun('ahoy', ['start'], [], [], $this->defaultTimeout, $this->defaultIdleTimeout);
    $this->assertProcessSuccessful();

    $this->processRun('ahoy', ['provision'], [], [], $this->longTimeout, $this->defaultIdleTimeout);
    $this->assertProcessSuccessful();
    $this->assertProcessAnyOutputContains('PROVISION COMPLETE');
    $this->assertProcessAnyOutputNotContains('Do you really want to drop all tables in the database');

    $this->processRun('ahoy', ['provision'], [], [], $this->longTimeout, $this->defaultIdleTimeout);
    $this->assertProcessSuccessful();
    $this->assertProcessAnyOutputContains('PROVISION COMPLETE');
    $this->assertProcessAnyOutputNotContains('Do you really want to drop all tables in the database');
  }

  public function testBuildBasicWorkflow(): void {
    $this->processRun('ahoy', ['build'], [], [], $this->longTimeout, $this->defaultIdleTimeout);
    $this->assertProcessSuccessful();
    $this->assertProcessAnyOutputContains('PROVISION COMPLETE');

    $this->processRun('ahoy', ['drush', 'status'], [], [], $this->defaultTimeout, $this->defaultIdleTimeout);
    $this->assertProcessSuccessful();
    $this->assertProcessAnyOutputContains('Database         : Connected');
    $this->assertProcessAnyOutputContains('Drupal bootstrap : Successful');

    $this->processRun('ahoy', ['login'], [], [], $this->defaultTimeout, $this->defaultIdleTimeout);
    $this->assertProcessSuccessful();
    $this->assertProcessAnyOutputContains('user/reset/1/');

    $this->processRun('ahoy', ['lint'], [], [], $this->defaultTimeout, $this->defaultIdleTimeout);
    $this->assertProcessSuccessful();

    $this->processRun('ahoy', ['test'], [], [], $this->longTimeout, $this->defaultIdleTimeout);
    $this->assertProcessSuccessful();
    $this->assertDirectoryExists(self::$sut . '/build/web/sites/simpletest/browser_output');
  }

  public function testLintAndLintFix(): void {
    $this->processRun('ahoy', ['assemble'], [], [], $this->longTimeout, $this->defaultIdleTimeout);
    $this->assertProcessSuccessful();

    $this->processRun('ahoy', ['lint'], [], [], $this->defaultTimeout, $this->defaultIdleTimeout);
    $this->assertProcessSuccessful();

    // PHP: introduce a PHPCS violation.
    File::append(self::$sut . '/your_extension.module', '$a=123;echo $a;' . PHP_EOL);

    $this->processRun('ahoy', ['lint'], [], [], $this->defaultTimeout, $this->defaultIdleTimeout);
    $this->assertProcessFailed();

    $this->processRun('ahoy', ['lint-fix'], [], [], $this->defaultTimeout, $this->defaultIdleTimeout);

    $this->processRun('ahoy', ['lint'], [], [], $this->defaultTimeout, $this->defaultIdleTimeout);
    $this->assertProcessSuccessful();

    // JS: introduce a Prettier formatting violation (fixable).
    File::append(self::$sut . '/js/your_extension.js', 'Drupal.behaviors.yourExtension.testValue =     "test"   ;' . PHP_EOL);

    $this->processRun('ahoy', ['lint'], [], [], $this->defaultTimeout, $this->defaultIdleTimeout);
    $this->assertProcessFailed();

    $this->processRun('ahoy', ['lint-fix'], [], [], $this->defaultTimeout, $this->defaultIdleTimeout);

    $this->processRun('ahoy', ['lint'], [], [], $this->defaultTimeout, $this->defaultIdleTimeout);
    $this->assertProcessSuccessful();

    // CSS: introduce a Stylelint order violation (fixable).
    File::append(self::$sut . '/css/your_extension.css', PHP_EOL . '.test {' . PHP_EOL . '  z-index: 1;' . PHP_EOL . '  color: red;' . PHP_EOL . '}' . PHP_EOL);

    $this->processRun('ahoy', ['lint'], [], [], $this->defaultTimeout, $this->defaultIdleTimeout);
    $this->assertProcessFailed();

    $this->processRun('ahoy', ['lint-fix'], [], [], $this->defaultTimeout, $this->defaultIdleTimeout);

    $this->processRun('ahoy', ['lint'], [], [], $this->defaultTimeout, $this->defaultIdleTimeout);
    $this->assertProcessSuccessful();
  }

  public function testJsTestFailure(): void {
    $this->processRun('ahoy', ['assemble'], [], [], $this->longTimeout, $this->defaultIdleTimeout);
    $this->assertProcessSuccessful();

    $this->processRun('ahoy', ['test-js'], [], [], $this->defaultTimeout, $this->defaultIdleTimeout);
    $this->assertProcessSuccessful();

    File::replaceContentInFile(self::$sut . '/js/your_extension.test.js', 'toMatch', 'not.toMatch');

    $this->processRun('ahoy', ['test-js'], [], [], $this->defaultTimeout, $this->defaultIdleTimeout);
    $this->assertProcessFailed();
  }

  public function testUnitTestFailure(): void {
    $this->processRun('ahoy', ['assemble'], [], [], $this->longTimeout, $this->defaultIdleTimeout);
    $this->assertProcessSuccessful();

    $this->processRun('ahoy', ['test-unit'], [], [], $this->longTimeout, $this->defaultIdleTimeout);
    $this->assertProcessSuccessful();

    $this->assertTestCoverage();

    File::replaceContentInFile(self::$sut . '/tests/src/Unit/YourExtensionServiceUnitTest.php', 'assertEquals', 'assertNotEquals');

    $this->processRun('ahoy', ['test-unit'], [], [], $this->longTimeout, $this->defaultIdleTimeout);
    $this->assertProcessFailed();
  }

  public function testFunctionalTestFailure(): void {
    $this->processRun('ahoy', ['build'], [], [], $this->longTimeout, $this->defaultIdleTimeout);
    $this->assertProcessSuccessful();

    $this->processRun('ahoy', ['test-functional'], [], [], $this->longTimeout, $this->defaultIdleTimeout);
    $this->assertProcessSuccessful();
    $this->assertDirectoryExists(self::$sut . '/build/web/sites/simpletest/browser_output');

    File::replaceContentInFile(self::$sut . '/tests/src/Functional/YourExtensionFunctionalTest.php', 'responseContains', 'responseNotContains');

    $this->processRun('ahoy', ['test-functional'], [], [], $this->longTimeout, $this->defaultIdleTimeout);
    $this->assertProcessFailed();
  }

  public function testKernelTestFailure(): void {
    $this->processRun('ahoy', ['build'], [], [], $this->longTimeout, $this->defaultIdleTimeout);
    $this->assertProcessSuccessful();

    $this->processRun('ahoy', ['test-kernel'], [], [], $this->longTimeout, $this->defaultIdleTimeout);
    $this->assertProcessSuccessful();

    File::replaceContentInFile(self::$sut . '/tests/src/Kernel/YourExtensionServiceKernelTest.php', 'assertEquals', 'assertNotEquals');

    $this->processRun('ahoy', ['test-kernel'], [], [], $this->longTimeout, $this->defaultIdleTimeout);
    $this->assertProcessFailed();
  }

}
