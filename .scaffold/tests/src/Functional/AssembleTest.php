<?php

declare(strict_types=1);

namespace AlexSkrypnyk\drupal_extension_scaffold\Tests\Functional;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests for the assemble devtools command.
 *
 * phpcs:disable Drupal.Commenting.FunctionComment.Missing
 * phpcs:disable Drupal.Commenting.DocComment.MissingShort
 */
#[Group('p2')]
final class AssembleTest extends DevtoolsTestCase {

  #[DataProvider('dataProviderAssemble')]
  public function testAssemble(string $drupal_version, string $init_message): void {
    $env = $drupal_version !== '' ? ['DRUPAL_VERSION' => $drupal_version] : [];

    $this->processRun('./.devtools/assemble', [], [], $env, $this->longTimeout, $this->defaultIdleTimeout);
    $this->assertProcessSuccessful();

    $this->assertProcessAnyOutputContains($init_message);
    $this->assertProcessAnyOutputContains('ASSEMBLE COMPLETE');
    $this->assertDirectoryExists(self::$sut . '/build/vendor');
    $this->assertFileExists(self::$sut . '/build/composer.json');
    $this->assertFileExists(self::$sut . '/build/composer.lock');

    // @see https://github.com/composer/composer/issues/12215
    $this->processRun('composer', ['--working-dir=' . self::$sut . '/build', 'require', '--dev', 'drupal/coder', '--with-all-dependencies', '--dry-run'], [], [], $this->defaultTimeout, $this->defaultIdleTimeout);
    $this->assertProcessAnyOutputNotContains('Upgrading');
  }

  public static function dataProviderAssemble(): \Iterator {
    yield ['', 'Creating Drupal 11 project'];
    yield ['10', 'Creating Drupal 10 project'];
  }

}
