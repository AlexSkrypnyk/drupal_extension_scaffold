<?php

declare(strict_types=1);

namespace AlexSkrypnyk\drupal_extension_scaffold\Tests\Functional;

use AlexSkrypnyk\drupal_extension_scaffold\Tests\Unit\UnitTestCase;
use AlexSkrypnyk\File\File;
use AlexSkrypnyk\PhpunitHelpers\Traits\ProcessTrait;
use AlexSkrypnyk\PhpunitHelpers\Traits\TuiTrait;
use AlexSkrypnyk\Snapshot\Testing\SnapshotTrait;

/**
 * Class UnitTestCase.
 *
 * UnitTestCase fixture class.
 *
 * phpcs:disable Drupal.Commenting.FunctionComment.Missing
 * phpcs:disable Drupal.Commenting.DocComment.MissingShort
 */
abstract class FunctionalTestCase extends UnitTestCase {

  use ProcessTrait;
  use SnapshotTrait;
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
      '.claude',
      'build',
    ]);

    // Change the current working directory to the 'system under test'.
    chdir(static::$sut);
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    if (empty(static::$fixtures)) {
      throw new \RuntimeException('Fixtures directory is not set.');
    }

    // Use SnapshotTrait's snapshotUpdateOnFailure() for automatic updates.
    if (str_contains(static::$fixtures, DIRECTORY_SEPARATOR . 'init' . DIRECTORY_SEPARATOR)) {
      $this->snapshotUpdateOnFailure(static::$fixtures, static::$sut, static::$tmp);
    }

    static::envReset();
    static::processTearDown();

    parent::tearDown();
  }

  /**
   * {@inheritdoc}
   */
  public static function locationsFixturesDir(): string {
    return 'fixtures';
  }

  protected function replaceVersions(string $dir): void {
    File::getReplacer()
      ->addVersionReplacements()
      ->addExclusions(['127.0.0.1'])
      // Increase max replacements to handle large files with many version
      // strings (GHA workflows, lock files, etc). This value was empirically
      // derived through repeated trials.
      ->setMaxReplacements(5)
      ->replaceInDir($dir);
  }

  protected function snapshotUpdateBefore(string $actual): void {
    $this->replaceVersions($actual);
  }

}
