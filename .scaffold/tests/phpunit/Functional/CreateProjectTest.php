<?php

declare(strict_types=1);

namespace AlexSkrypnyk\drupal_extension_scaffold\Scaffold\Tests\Functional;

use AlexSkrypnyk\Customizer\CustomizeCommand;
use AlexSkrypnyk\Customizer\Tests\Dirs;
use AlexSkrypnyk\Customizer\Tests\Functional\CustomizerTestCase;
use Symfony\Component\Finder\Finder;

/**
 * Test Customizer as a dependency during `composer create-project`.
 */
class CreateProjectTest extends CustomizerTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    if (!isset(static::$composerJsonFile)) {
      throw new \RuntimeException('The $composerJsonFile property must be set in the child class.');
    }

    $reflector = new \ReflectionClass(CustomizeCommand::class);
    $this->customizerFile = basename((string) $reflector->getFileName());

    // Initialize the Composer command tester.
    $this->composerCommandInit();

    // Initialize the directories.
    $this->dirsInit(static function (Dirs $dirs): void {
      $dirs->fixtures = $dirs->root . '/.scaffold/tests/phpunit/Fixtures';

      // Exclude the 'vendor' and 'composer.lock' files to make sure that
      // this test dependencies do not leak into SUT. Otherwise, Composer
      // will assume that the root package is already installed and will not
      // run installation events.
      $finder = new Finder();
      $finder
        ->ignoreDotFiles(FALSE)
        ->ignoreVCS(TRUE)
        ->files()
        ->exclude(['vendor', 'node_modules', '.idea'])
        ->notName('composer.lock')
        ->in($dirs->root);

      $dirs->fs->mirror(
        $dirs->root,
        $dirs->repo,
        $finder->getIterator()
      );

      $dirs->fs->remove($dirs->repo . DIRECTORY_SEPARATOR . CustomizeCommand::CONFIG_FILE);
      $dirs->fs->symlink(
        $dirs->root . DIRECTORY_SEPARATOR . CustomizeCommand::CONFIG_FILE,
        $dirs->repo . DIRECTORY_SEPARATOR . CustomizeCommand::CONFIG_FILE,
        TRUE
      );
    }, (string) getcwd());

    // Projects using this project through a plugin need to have this
    // repository added to their composer.json to be able to download it
    // during the test.
    $json = $this->composerJsonRead($this->dirs->repo . '/composer.json');
    $json['repositories'] = [
      [
        'type' => 'path',
        'url' => $this->dirs->root,
        'options' => ['symlink' => TRUE],
      ],
    ];
    $this->composerJsonWrite($this->dirs->repo . '/composer.json', $json);

    // Save the package name for later use in tests.
    $this->packageName = $json['name'];

    // Change the current working directory to the 'system under test'.
    chdir($this->dirs->sut);
  }

  /**
   * Test the creation of a project with the Customizer.
   */
  public function testInstall(): void {
    $this->customizerSetAnswers([
      'testorg/testpackage',
      'Test description',
      'module',
      'GitHub Actions',
      'Ahot',
      self::TUI_ANSWER_NOTHING,
    ]);
    $this->composerCreateProject([
      '--repository' => [
        json_encode([
          'type' => 'path',
          'url' => $this->dirs->repo,
          'options' => ['symlink' => TRUE],
        ]),
      ],
    ]);

    $this->assertComposerCommandSuccessOutputContains('Welcome to the Drupal Extension Scaffold project customizer');
    $this->assertComposerCommandSuccessOutputContains('Project was customized');

    $this->assertFixtureFiles('install1');
  }

  /**
   * Assert that the fixture files match the actual files.
   *
   * @param string $name
   *   The name of the fixture files.
   */
  protected function assertFixtureFiles(string $name): void {
    $expected = $this->dirs->fixtures . '/' . $name;
    $actual = $this->dirs->sut;

    if (!empty(getenv('UPDATE_TEST_FIXTURES'))) {
      $this->dirs->fs->remove($expected);
      $this->dirs->fs->mirror($actual, $expected);
    }
    else {
      $this->assertDirsEqual($expected, $actual);
    }
  }

  /**
   * Compare directories.
   *
   * @param string|array $expected
   *   The expected directory.
   * @param string $actual
   *   The actual directory.
   */
  protected function assertDirsEqual(string|array $expected, string $actual): void {
    $finder = new Finder();
    $finder
      ->ignoreDotFiles(FALSE)
      ->ignoreVCS(TRUE)
      ->files()
      ->in($expected);

    foreach ($finder as $file) {
      $this->assertFileExists($actual . '/' . $file->getRelativePathname());
      $this->assertFileEquals($file->getPathname(), $actual . '/' . $file->getRelativePathname());
    }
  }

}
