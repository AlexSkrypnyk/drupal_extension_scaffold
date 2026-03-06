<?php

declare(strict_types=1);

namespace AlexSkrypnyk\drupal_extension_scaffold\Tests\Functional;

use AlexSkrypnyk\File\File;
use AlexSkrypnyk\Snapshot\Testing\SnapshotTrait;
use Laravel\SerializableClosure\SerializableClosure;
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
  public function testInit(
    array $answers = [],
    array $expected = [],
    ?SerializableClosure $before = NULL,
    ?SerializableClosure $after = NULL,
    bool $no_interaction = FALSE,
  ): void {
    self::$fixtures = static::locationsFixtureDir();

    if ($before instanceof SerializableClosure) {
      $before = self::cu($before);
      $before($this);
    }

    if ($no_interaction) {
      $args = ['Force Crystal', 'force_crystal', 'module', 'gha', 'ahoy', '--no-interaction'];
      $this->processRun(self::$sut . DIRECTORY_SEPARATOR . 'init.php', $args);
    }
    else {
      $answers = self::tuiEntries(array_replace(self::defaultAnswers(), $answers));
      $this->processRun(self::$sut . DIRECTORY_SEPARATOR . 'init.php', [], $answers);
    }

    $this->assertProcessSuccessful();

    $expected = array_merge([
      'Please follow the prompts to adjust your extension configuration',
      'Initialization complete.',
    ], $expected);

    $this->assertProcessOutputContainsOrNot($expected);

    $baseline = File::dir(self::$fixtures . '/../' . self::BASELINE_DIR);
    $this->replaceVersions(self::$sut);

    if (!is_string(self::$fixtures)) {
      throw new \RuntimeException('Fixtures directory is not set.');
    }
    $this->assertSnapshotMatchesBaseline(self::$sut, $baseline, self::$fixtures);

    if ($after instanceof SerializableClosure) {
      $after = self::cu($after);
      $after($this);
    }
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
        'remove_self' => 'n',
      ],
    ];

    yield 'no_interaction' => [
      [],
      [],
      NULL,
      NULL,
      TRUE,
    ];
  }

  protected static function defaultAnswers(): array {
    return [
      'name' => 'Force Crystal',
      'machine_name' => self::TUI_DEFAULT,
      'type' => self::TUI_DEFAULT,
      'ci_provider' => self::TUI_DEFAULT,
      'command_wrapper' => self::TUI_DEFAULT,
      'remove_self' => self::TUI_DEFAULT,
      'proceed' => self::TUI_DEFAULT,
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
