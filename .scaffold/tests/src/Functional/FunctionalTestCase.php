<?php

declare(strict_types=1);

namespace AlexSkrypnyk\drupal_extension_scaffold\Tests\Functional;

use AlexSkrypnyk\drupal_extension_scaffold\Tests\Unit\UnitTestCase;
use AlexSkrypnyk\PhpunitHelpers\Traits\ProcessTrait;
use AlexSkrypnyk\PhpunitHelpers\Traits\TuiTrait;

/**
 * FunctionalTestCase fixture class.
 *
 * phpcs:disable Drupal.Commenting.FunctionComment.Missing
 * phpcs:disable Drupal.Commenting.DocComment.MissingShort
 */
abstract class FunctionalTestCase extends UnitTestCase {

  use ProcessTrait;
  use TuiTrait;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    static::locationsCopy('../../', static::$sut, [], [
      '*.png',
      '.idea',
      '.logs',
      '.phpunit.cache',
      '.artifacts',
      'build',
    ]);

    chdir(static::$sut);
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    if (empty(static::$fixtures)) {
      throw new \RuntimeException('Fixtures directory is not set.');
    }

    static::processTearDown();

    parent::tearDown();
  }

  /**
   * {@inheritdoc}
   */
  public static function locationsFixturesDir(): string {
    return 'fixtures';
  }

}
