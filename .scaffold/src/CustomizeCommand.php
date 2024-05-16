<?php

declare(strict_types=1);

namespace Scaffold;

use Composer\Command\BaseCommand;
use Composer\Console\Input\InputOption;
use Composer\Util\Filesystem;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Finder\Finder;

/**
 * Customize the project based on the answers provided by the user.
 *
 * This is a single-file Symfony Console Command class designed to be a drop-in
 * for any scaffold, template, or boilerplate project. It provides a way to ask
 * questions and process answers to customize user's project.
 *
 * It is designed to be called during the `composer create-project` command,
 * including when it is run with the `--no-install` option. It relies only on
 * the components provided by Composer.
 *
 * It also supports passing answers as a JSON string via the `--answers` option
 * or the `CUSTOMIZER_ANSWERS` environment variable.
 *
 * If you are a scaffold project maintainer and want to use this class to
 * provide a customizer for your project, you can copy this class to your
 * project, adjust the namespace, $project variable, and implement the
 * `questions()` method to tailor the customizer to your scaffold's needs.
 */
class CustomizeCommand extends BaseCommand {

  /**
   * Project name.
   */
  protected static string $project = 'drupal_extension_scaffold';

  /**
   * IO.
   */
  protected SymfonyStyle $io;

  /**
   * Current working directory.
   */
  protected string $cwd;

  /**
   * Filesystem utility.
   */
  protected Filesystem $fs;

  /**
   * Question definitions.
   *
   * Define questions and their processing callbacks. Questions will be asked
   * in the order they are defined. Questions can use answers from previous
   * questions received so far.
   *
   * Answers will be processed in the order they are defined. Process callbacks
   * have access to all answers and current class' properties and methods.
   * If a question does not have a process callback, a method prefixed with
   * 'process' and a camel cased question title will be called. If the method
   * does not exist, there will be no processing.
   *
   * @code
   * $questions['Machine name'] = [
   *   // Question callback function.
   *   'question' => fn(array $answers) => $this->io->ask(
   *     // Question text to show to the user.
   *     'What is your machine name',
   *     // Default answer.
   *     Str2Name::machine(basename($this->cwd)),
   *     // Answer validation function.
   *     static fn(string $string): string => strtolower($string)
   *   ),
   *   // Process callback function.
   *   'process' => function (string $title, string $answer, array $answers): void {
   *     // Remove a directory using 'fs' and `cwd` class properties.
   *     $this->fs->removeDirectory($this->cwd . '/somedir');
   *     // Replace a string in a file using 'cwd' class property and
   *     /  'replaceInPath' method.
   *     $this->replaceInPath($this->cwd . '/somefile', 'old', 'new');
   *     // Replace a string in al files in a directory.
   *     $this->replaceInPath($this->cwd . '/somedir', 'old', 'new');
   *   },
   * ];
   * @endcode
   *
   * @return array<string,array<string,string|callable>>
   *   An associative array of questions with question title as key and the
   *   question data array with the following keys:
   *   - question: The question callback function used to ask the question. The
   *     callback receives an associative array of all answers received so far.
   *   - process: The callback function used to process the answer. Callback can
   *     be an anonymous function or a method of this class as
   *     process<PascalCasedQuestion>. The callback will receive the following
   *     arguments:
   *     - title: The current question title.
   *     - answer: The answer to the current question.
   *     - answers: An associative array of all answers.
   *
   * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
   * @SuppressWarnings(PHPMD.UnusedFormalParameter)
   */
  protected function questions(): array {
    $questions = [];

    $questions['Machine name'] = [
      'question' => fn(array $answers): mixed => $this->io->ask(
        'Extension machine name',
        Str2Name::machine(basename($this->cwd)),
        static fn(string $string): string => Str2Name::machine($string)
      ),
      'process' => static function (string $title, string $answer, array $answers): void {
        sleep(1);
      },
    ];

    $questions['Title'] = [
      'question' => fn(array $answers): mixed => $this->io->ask(
        'Extension title',
        Str2Name::sentence($answers['Machine name'] ?? ''),
        static fn(string $string): string => Str2Name::sentence($string)
      ),
      'process' => static function (string $title, string $answer, array $answers): void {
        sleep(1);
      },
    ];

    $questions['Type'] = [
      'question' => fn(array $answers): mixed => $this->io->choice(
        'Extension type',
        ['module', 'theme'],
        'module'
      ),
      'process' => static function (string $title, string $answer, array $answers): void {
        sleep(1);
      },
    ];

    $questions['CI provider'] = [
      'question' => fn(array $answers): mixed => $this->io->choice(
        'CI provider',
        ['GitHub Actions', 'CircleCI'],
        'GitHub Actions'
      ),
      'process' => static function (string $title, string $answer, array $answers): void {
        if ($answer === 'GitHub Actions' || $answer === 'None') {
          $this->fs->removeDirectory($this->cwd . '/.circleci');
        }
        if ($answer === 'GitHub Actions' || $answer === 'None') {
          $this->fs->remove($this->cwd . '/.github/test.yml');
          $this->fs->remove($this->cwd . '/.github/deploy.yml');
        }
      },
    ];

    // Define the 'Command wrapper' questions with its question function.
    $questions['Command wrapper'] = [
      'question' => fn(array $answers): mixed => $this->io->choice(
        'Command wrapper',
        ['Ahoy', 'Makefile', 'None'],
        'Ahoy'
      ),
      'process' => static function (string $title, string $answer, array $answers): void {
        sleep(1);
      },
    ];

    return $questions;
  }

  /**
   * {@inheritdoc}
   */
  protected function configure(): void {
    $this
      ->setName('customize')
      ->setDescription(sprintf('Customize %s project', static::$project))
      ->setDefinition([
        new InputOption('answers', NULL, InputOption::VALUE_REQUIRED, 'Answers to questions passed as a JSON string.'),
      ]);
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output): int {
    $this->io = $this->initIo($input, $output);
    $this->cwd = (string) getcwd();
    $this->fs = new Filesystem();

    $this->io->title(sprintf('Welcome to %s project customizer', static::$project));

    $this->io->block([
      'Please answer the following questions to customize your project.',
      'You will be able to review your answers before proceeding.',
      'Press Ctrl+C to exit.',
    ]);

    $answers = $this->askQuestions();

    $this->io->definitionList(
      ['QUESTIONS' => 'ANSWERS'],
      new TableSeparator(),
      ...array_map(static fn($q, $a): array => [$q => $a], array_keys($answers), array_column($answers, 'answer'))
    );

    if (!$this->io->confirm('Proceed?')) {
      $this->io->success('No changes were made.');

      return 0;
    }

    $this->process($answers);

    $this->io->newLine();
    $this->io->success('Project was customized.');

    return 0;
  }

  /**
   * Collect questions from self::questions(), ask them and return the answers.
   *
   * @return array<string,array<string,string|callable>>
   *   The answers to the questions as an associative array:
   *   - key: The question key.
   *   - value: An associative array with the following keys:
   *     - answer: The answer to the question.
   *     - callback: The callback to process the answer. If not specified, a
   *       method prefixed with 'process' and a camel cased question will be
   *       called. If the method does not exist, there will be no processing.
   */
  protected function askQuestions(): array {
    $questions = $this->questions();

    $answers = [];
    foreach ($questions as $title => $callbacks) {
      if (!is_callable($callbacks['question'] ?? '')) {
        throw new \RuntimeException(sprintf('Question "%s" must be callable', $title));
      }

      $answers[$title]['answer'] = $callbacks['question'](array_combine(array_keys($answers), array_column($answers, 'answer')));

      $answers[$title]['process'] = $callbacks['process'] ?? NULL;
      if (!empty($answers[$title]['process']) && !is_callable($answers[$title]['process'])) {
        throw new \RuntimeException(sprintf('Process callback "%s" must be callable', $answers[$title]['process']));
      }

      if (empty($answers[$title]['process'])) {
        $method = str_replace(' ', '', str_replace(['-', '_'], ' ', ucwords('process ' . $title)));
        if (method_exists($this, $method)) {
          if (!is_callable([$this, $method])) {
            throw new \RuntimeException(sprintf('Process method "%s" must be callable', $method));
          }
          $answers[$title]['callback'] = $method;
        }
      }
    }

    return $answers;
  }

  /**
   * Process questions.
   *
   * @param array<string,array<string,string|callable>> $answers
   *   Prompts.
   */
  protected function process(array $answers): void {
    $progress = $this->io->createProgressBar(count($answers));
    $progress->setFormat(' %current%/%max% [%bar%] %percent:3s%% - %message%');
    $progress->setMessage('Starting processing');
    $progress->start();
    foreach ($answers as $title => $answer) {
      $progress->setMessage(sprintf('Processed: %s', OutputFormatter::escape($title)));
      if (!empty($answer['process']) && is_callable($answer['process'])) {
        call_user_func_array($answer['process'], [
          $title,
          $answer['answer'],
          array_combine(array_keys($answers), array_column($answers, 'answer')),
        ]);
      }
      $progress->advance();
    }
    $progress->setMessage('Done');
    $progress->finish();
    $this->io->newLine();
  }

  /**
   * Initialize IO.
   */
  protected static function initIo(InputInterface $input, OutputInterface $output): SymfonyStyle {
    $answers = getenv('CUSTOMIZER_ANSWERS');
    $from_env = !$answers;
    $answers = $answers ?: $input->getOption('answers');

    if ($answers && is_string($answers)) {
      $answers = json_decode($answers, TRUE);

      if (is_array($answers)) {
        if ($from_env) {
          $stream = fopen('php://memory', 'r+');
          if ($stream === FALSE) {
            throw new \RuntimeException('Failed to open memory stream');
          }
          foreach ($answers as $answer) {
            fwrite($stream, $answer . \PHP_EOL);
          }
          rewind($stream);

          $input = new ArgvInput($answers);
          $input->setStream($stream);
        }
        else {
          $input = new ArgvInput($answers);
        }
      }
    }

    return new SymfonyStyle($input, $output);
  }

  /**
   * Replace in path.
   *
   * @param string $path
   *   Path: directory or file.
   * @param string|array<int, string> $search
   *   Search string or array of strings.
   * @param string|array<int,string> $replace
   *   Replace string or array of strings.
   * @param bool $replace_line
   *   Replace for a whole line or only the occurrence.
   */
  protected static function replaceInPath(string $path, string|array $search, string|array $replace, bool $replace_line = FALSE): void {
    $dir = dirname($path);
    $filename = basename($path);

    if (is_dir($path)) {
      $dir = $path;
      $filename = NULL;
    }

    $finder = Finder::create()
      ->ignoreVCS(TRUE)
      ->ignoreDotFiles(FALSE)
      ->depth(0)
      ->files()
      ->contains($search)
      ->in($dir);

    if ($filename) {
      $finder->name($filename);
    }

    foreach ($finder as $file) {
      $file_path = $file->getRealPath();
      $file_content = file_get_contents($file_path);
      if ($file_content !== FALSE) {
        if ($replace_line) {
          $search = array_map(static fn($s): string => "/^.*{$s}.*\n/m", (array) $search);
        }

        $new_content = str_replace($search, $replace, $file_content);

        if ($new_content !== $file_content) {
          file_put_contents($file_path, $new_content);
        }
      }
    }
  }

}
