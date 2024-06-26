<?php

declare(strict_types=1);

use AlexSkrypnyk\Customizer\CustomizeCommand;
use AlexSkrypnyk\Str2Name\Str2Name;

/**
 * Customizer configuration.
 *
 * Customizer configuration for the Drupal Extension Scaffold project.
 *
 * phpcs:disable Drupal.Classes.ClassFileName.NoMatch
 *
 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
 */
class Customize {

  /**
   * {@inheritdoc}
   */
  public static function messages(CustomizeCommand $c): array {
    return [
      'welcome' => 'Welcome to the Drupal Extension Scaffold project customizer',
    ];
  }

  /**
   * @SuppressWarnings(PHPMD.CyclomaticComplexity)
   * @SuppressWarnings(PHPMD.StaticAccess)
   * @SuppressWarnings(PHPMD.ElseExpression)
   */
  public static function questions(CustomizeCommand $c): array {
    return [
      'Name' => [
        'question' => static fn(array $answers, CustomizeCommand $c): mixed => $c->io->ask('Package as namespace/name', NULL, static function (string $value): string {
          if (Str2Name::phpPackage($value) !== $value) {
            throw new \InvalidArgumentException(sprintf('The package name "%s" is invalid, it should be lowercase and have a vendor name, a forward slash, and a package name.', $value));
          }

          return $value;
        }),
        'process' => static function (string $title, string $answer, array $answers, CustomizeCommand $c): void {
          $c->packageData['name'] = $answer;
          $c->writeComposerJson($c->cwd . '/composer.json', $c->packageData);
        },
      ],
      'Description' => [
        'question' => static fn(array $answers, CustomizeCommand $c): mixed => $c->io->ask('Description'),
        'process' => static function (string $title, string $answer, array $answers, CustomizeCommand $c): void {
          $description = is_string($c->packageData['description']) ? $c->packageData['description'] : '';
          $c->packageData['description'] = $answer;
          $c->writeComposerJson($c->cwd . '/composer.json', $c->packageData);
          $c->replaceInPath($c->cwd, $description, $answer);
        },
      ],
      'Type' => [
        'question' => static fn(array $answers, CustomizeCommand $c): mixed => $c->io->choice('License type', [
          'module',
          'theme',
        ], 'module'),
        'process' => static function (string $title, string $answer, array $answers, CustomizeCommand $c): void {
          if ($answer === 'module') {
            $c->fs->remove($c->cwd . '/package.json');
            $c->fs->remove($c->cwd . '/package-lock.json');
          }
          else {
            $contents = file_get_contents($c->cwd . DIRECTORY_SEPARATOR . $c->packageData['name'] . '.info.yml');
            $contents .= "\n";
            $contents .= 'base theme: false';
            file_put_contents($c->cwd . DIRECTORY_SEPARATOR . $c->packageData['name'] . '.info.yml', $contents);
          }
        },
      ],
      'CI Provider' => [
        'question' => static fn(array $answers, CustomizeCommand $c): mixed => $c->io->choice('CI Provider', [
          'GitHub Actions',
          'CircleCI',
        ], 'GitHub Actions'),
        'process' => static function (string $title, string $answer, array $answers, CustomizeCommand $c): void {
          if ($answer === 'GitHub Actions' || $answer === 'None') {
            $c->fs->removeDirectory($c->cwd . '/.circleci');
          }
          if ($answer === 'GitHub Actions' || $answer === 'None') {
            $c->fs->remove($c->cwd . '/.github/test.yml');
            $c->fs->remove($c->cwd . '/.github/deploy.yml');
          }
        },
      ],
      'Command wrapper' => [
        'question' => static fn(array $answers, CustomizeCommand $c): mixed => $c->io->choice('Command wrapper', [
          'Ahoy',
          'Makefile',
          'None',
        ], 'Ahoy'),
        'process' => static function (string $title, string $answer, array $answers, CustomizeCommand $c): void {
          if ($answer === 'Makefile' || $answer === 'None') {
            $c->fs->remove($c->cwd . '/ahoy.yml');
          }
          if ($answer === 'Ahoy' || $answer === 'None') {
            $c->fs->remove($c->cwd . '/Makefile');
          }
        },
      ],
    ];
  }

  /**
   *
   */
  public static function cleanup(array &$composerjson, CustomizeCommand $c): void {
    $c->fs->copyThenRemove($c->cwd . '/README.dist.md', $c->cwd . '/README.md');

    $c->fs->remove($c->cwd . '/.github/FUNDING.yml');
    $c->fs->remove($c->cwd . '/.github/workflows/scaffold-release.yml');
    $c->fs->remove($c->cwd . '/.github/workflows/scaffold-test.yml');
    $c->fs->remove($c->cwd . '/customize.php');
    $c->fs->remove($c->cwd . '/.scaffold');
    $c->fs->remove($c->cwd . '/build');
    $c->fs->remove($c->cwd . '/vendor');
    $c->fs->remove($c->cwd . '/LICENSE');
    $c->fs->remove($c->cwd . '/README.dist.md');

    $composerjson = [];
    $c->fs->remove($c->cwd . '/composer.json');
    $c->fs->remove($c->cwd . '/composer.lock');
    $c->fs->copyThenRemove($c->cwd . '/composer.dist.json', $c->cwd . '/composer.json');
    $c->fs->remove($c->cwd . 'vendor');

    static::processGitattributes($c);
  }

  /**
   *
   */
  protected static function processGitattributes(CustomizeCommand $c): void {
    $c::replaceInPath($c->cwd . '/.gitattributes', '# Uncomment the lines below in your project.', '', TRUE);
    $c::uncommentLine($c->cwd . '/.gitattributes', '.ahoy.yml');
    $c::uncommentLine($c->cwd . '/.gitattributes', '.circleci');
    $c::uncommentLine($c->cwd . '/.gitattributes', '.devtools');
    $c::uncommentLine($c->cwd . '/.gitattributes', '.editorconfig');
    $c::uncommentLine($c->cwd . '/.gitattributes', '.gitattributes');
    $c::uncommentLine($c->cwd . '/.gitattributes', '.github');
    $c::uncommentLine($c->cwd . '/.gitattributes', '.gitignore');
    $c::uncommentLine($c->cwd . '/.gitattributes', '.twig-cs-fixer.php');
    $c::uncommentLine($c->cwd . '/.gitattributes', 'Makefile');
    $c::uncommentLine($c->cwd . '/.gitattributes', 'composer.dev.json');
    $c::uncommentLine($c->cwd . '/.gitattributes', 'phpcs.xml');
    $c::uncommentLine($c->cwd . '/.gitattributes', 'phpmd.xml');
    $c::uncommentLine($c->cwd . '/.gitattributes', 'phpstan.neon');
    $c::uncommentLine($c->cwd . '/.gitattributes', 'rector.php');
    $c::uncommentLine($c->cwd . '/.gitattributes', 'renovate.json');
    $c::uncommentLine($c->cwd . '/.gitattributes', 'tests');
    $c::replaceInPath($c->cwd . '/.gitattributes', '/\R?# Remove the lines below in your project.\R?/', '', TRUE);
    $c::replaceInPath($c->cwd . '/.gitattributes', '.github/FUNDING.yml', '', TRUE);
    $c::replaceInPath($c->cwd . '/.gitattributes', 'LICENSE', '', TRUE);
  }

}
