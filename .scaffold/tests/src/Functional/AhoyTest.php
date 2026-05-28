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

  public function testWorkflow(): void {
    // Start without build fails.
    $this->processRun('ahoy', ['start'], [], [], $this->defaultTimeout, $this->defaultIdleTimeout);
    $this->assertProcessFailed();
    $this->assertProcessAnyOutputNotContains('ENVIRONMENT READY');

    // Stop without build succeeds.
    $this->processRun('ahoy', ['stop'], [], [], $this->defaultTimeout, $this->defaultIdleTimeout);
    $this->assertProcessSuccessful();
    $this->assertProcessAnyOutputContains('ENVIRONMENT STOPPED');

    $this->processRun('ahoy', ['assemble'], [], [], $this->longTimeout, $this->defaultIdleTimeout);
    $this->assertProcessSuccessful();
    $this->assertProcessAnyOutputContains('ASSEMBLE COMPLETE');
    $this->assertDirectoryExists(self::$sut . '/build/vendor');
    $this->assertFileExists(self::$sut . '/build/composer.json');
    $this->assertFileExists(self::$sut . '/build/composer.lock');
    $this->assertDirectoryExists(self::$sut . '/build/node_modules');
    $this->assertProcessAnyOutputContains('Would run build');

    $this->processRun('ahoy', ['start'], [], [], $this->defaultTimeout, $this->defaultIdleTimeout);
    $this->assertProcessSuccessful();
    $this->assertProcessAnyOutputContains('ENVIRONMENT READY');

    $this->processRun('ahoy', ['stop'], [], [], $this->defaultTimeout, $this->defaultIdleTimeout);
    $this->assertProcessSuccessful();
    $this->assertProcessAnyOutputContains('ENVIRONMENT STOPPED');

    $this->processRun('ahoy', ['start'], [], [], $this->defaultTimeout, $this->defaultIdleTimeout);
    $this->assertProcessSuccessful();

    $this->processRun('ahoy', ['provision'], [], [], $this->longTimeout, $this->defaultIdleTimeout);
    $this->assertProcessSuccessful();
    $this->assertProcessAnyOutputContains('PROVISION COMPLETE');
    $this->assertProcessAnyOutputNotContains('Do you really want to drop all tables in the database');

    // Provision is idempotent.
    $this->processRun('ahoy', ['provision'], [], [], $this->longTimeout, $this->defaultIdleTimeout);
    $this->assertProcessSuccessful();
    $this->assertProcessAnyOutputContains('PROVISION COMPLETE');
    $this->assertProcessAnyOutputNotContains('Do you really want to drop all tables in the database');

    $this->processRun('ahoy', ['drush', 'status'], [], [], $this->defaultTimeout, $this->defaultIdleTimeout);
    $this->assertProcessSuccessful();
    $this->assertProcessAnyOutputContains('Database         : Connected');
    $this->assertProcessAnyOutputContains('Drupal bootstrap : Successful');

    $this->processRun('ahoy', ['login'], [], [], $this->defaultTimeout, $this->defaultIdleTimeout);
    $this->assertProcessSuccessful();
    $this->assertProcessAnyOutputContains('user/reset/1/');

    $this->runLint();

    $this->runTests();

    $this->runJsTests();

    $this->runUnitTests();

    $this->runKernelTests();

    $this->runFunctionalTests();

    $this->runFunctionalJavascriptTests();
  }

  protected function runLint(): void {
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

  protected function runTests(): void {
    // `ahoy test` runs every PHPUnit suite in the consumer project, including
    // FunctionalJavascript. Skip on CI environments without Docker (macOS).
    if (getenv('SCAFFOLD_SKIP_FUNCTIONAL_JAVASCRIPT') === '1') {
      fwrite(STDERR, 'SCAFFOLD_SKIP_FUNCTIONAL_JAVASCRIPT=1: skipping `ahoy test` (includes FunctionalJavascript).' . PHP_EOL);

      return;
    }

    $this->processRun('ahoy', ['test'], [], [], $this->longTimeout, $this->defaultIdleTimeout);
    $this->assertProcessSuccessful();
    $this->assertDirectoryExists(self::$sut . '/build/web/sites/simpletest/browser_output');
  }

  protected function runJsTests(): void {
    $this->processRun('ahoy', ['test-js'], [], [], $this->defaultTimeout, $this->defaultIdleTimeout);
    $this->assertProcessSuccessful();

    File::replaceContentInFile(self::$sut . '/js/your_extension.test.js', 'toMatch', 'not.toMatch');

    $this->processRun('ahoy', ['test-js'], [], [], $this->defaultTimeout, $this->defaultIdleTimeout);
    $this->assertProcessFailed();
  }

  protected function runUnitTests(): void {
    $this->processRun('ahoy', ['test-unit'], [], [], $this->longTimeout, $this->defaultIdleTimeout);
    $this->assertProcessSuccessful();

    $this->assertTestCoverage();

    File::replaceContentInFile(self::$sut . '/tests/src/Unit/YourExtensionServiceUnitTest.php', 'assertEquals', 'assertNotEquals');

    $this->processRun('ahoy', ['test-unit'], [], [], $this->longTimeout, $this->defaultIdleTimeout);
    $this->assertProcessFailed();
  }

  protected function runKernelTests(): void {
    $this->processRun('ahoy', ['test-kernel'], [], [], $this->longTimeout, $this->defaultIdleTimeout);
    $this->assertProcessSuccessful();

    File::replaceContentInFile(self::$sut . '/tests/src/Kernel/YourExtensionServiceKernelTest.php', 'assertEquals', 'assertNotEquals');

    $this->processRun('ahoy', ['test-kernel'], [], [], $this->longTimeout, $this->defaultIdleTimeout);
    $this->assertProcessFailed();
  }

  protected function runFunctionalTests(): void {
    $this->processRun('ahoy', ['test-functional'], [], [], $this->longTimeout, $this->defaultIdleTimeout);
    $this->assertProcessSuccessful();
    $this->assertDirectoryExists(self::$sut . '/build/web/sites/simpletest/browser_output');

    File::replaceContentInFile(self::$sut . '/tests/src/Functional/YourExtensionFunctionalTest.php', 'responseContains', 'responseNotContains');

    $this->processRun('ahoy', ['test-functional'], [], [], $this->longTimeout, $this->defaultIdleTimeout);
    $this->assertProcessFailed();
  }

  protected function runFunctionalJavascriptTests(): void {
    // Allow CI environments without Docker (e.g. GitHub Actions macOS runners)
    // to opt out of the Selenium-backed FunctionalJavascript step.
    if (getenv('SCAFFOLD_SKIP_FUNCTIONAL_JAVASCRIPT') === '1') {
      fwrite(STDERR, 'SCAFFOLD_SKIP_FUNCTIONAL_JAVASCRIPT=1: skipping FunctionalJavascript step.' . PHP_EOL);

      return;
    }

    $this->processRun('ahoy', ['test-functional-javascript'], [], [], $this->longTimeout, $this->defaultIdleTimeout);
    $this->assertProcessSuccessful();

    File::replaceContentInFile(self::$sut . '/tests/src/FunctionalJavascript/YourExtensionSmokeJsTest.php', 'assertNotEmpty', 'assertEmpty');

    $this->processRun('ahoy', ['test-functional-javascript'], [], [], $this->longTimeout, $this->defaultIdleTimeout);
    $this->assertProcessFailed();
  }

}
