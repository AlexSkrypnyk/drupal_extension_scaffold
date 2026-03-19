<?php

declare(strict_types=1);

namespace AlexSkrypnyk\drupal_extension_scaffold\Tests\Functional;

use AlexSkrypnyk\File\File;
use AlexSkrypnyk\Snapshot\Testing\SnapshotTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Class InitTest.
 *
 * Functional tests for init.php script.
 */
#[Group('p1')]
final class InitTest extends FunctionalTestCase {

  use SnapshotTrait;

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    if (empty(self::$fixtures)) {
      throw new \RuntimeException('Fixtures directory is not set.');
    }

    // Use SnapshotTrait's snapshotUpdateOnFailure() for automatic updates.
    if (str_contains(self::$fixtures, DIRECTORY_SEPARATOR . 'init' . DIRECTORY_SEPARATOR)) {
      $this->snapshotUpdateOnFailure(self::$fixtures, self::$sut, self::$tmp);
    }

    parent::tearDown();
  }

  #[DataProvider('dataProviderInit')]
  public function testInit(array $answers = []): void {
    self::$fixtures = static::locationsFixtureDir();

    $answers = array_replace(self::defaultAnswers(), $answers);

    // Build Prompty env vars to pre-fill all prompts.
    $env = [];
    foreach ($answers as $key => $value) {
      $env['PROMPTY_' . strtoupper((string) $key)] = $value;
    }

    $this->processRun(self::$sut . DIRECTORY_SEPARATOR . 'init.php', [], [], $env);

    $this->assertProcessSuccessful();

    $this->assertProcessOutputContainsOrNot([
      'Drupal Extension Scaffold',
    ]);

    $baseline = File::dir(self::$fixtures . '/../' . self::BASELINE_DIR);
    $this->replaceVersions(self::$sut);

    if (!is_string(self::$fixtures)) {
      throw new \RuntimeException('Fixtures directory is not set.');
    }
    $this->assertSnapshotMatchesBaseline(self::$sut, $baseline, self::$fixtures);
  }

  public static function dataProviderInit(): \Iterator {
    yield self::BASELINE_DATASET => [
      [],
    ];

    yield 'theme' => [
      [
        'type' => 'theme',
      ],
    ];

    yield 'circleci' => [
      [
        'ci_provider' => 'circleci',
      ],
    ];

    yield 'gha_makefile' => [
      [
        'command_wrapper' => 'makefile',
      ],
    ];

    yield 'gha_no_command_wrapper' => [
      [
        'command_wrapper' => 'none',
      ],
    ];

    yield 'circleci_makefile' => [
      [
        'ci_provider' => 'circleci',
        'command_wrapper' => 'makefile',
      ],
    ];

    yield 'circleci_no_command_wrapper' => [
      [
        'ci_provider' => 'circleci',
        'command_wrapper' => 'none',
      ],
    ];

    yield 'keep_script' => [
      [
        'remove_self' => 'false',
      ],
    ];
  }

  protected static function defaultAnswers(): array {
    return [
      'name' => 'Force Crystal',
      'machine_name' => 'force_crystal',
      'type' => 'module',
      'ci_provider' => 'gha',
      'command_wrapper' => 'ahoy',
      'remove_self' => 'true',
      'proceed' => 'true',
    ];
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
