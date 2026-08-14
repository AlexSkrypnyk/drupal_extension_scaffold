<?php

declare(strict_types=1);

namespace AlexSkrypnyk\drupal_extension_scaffold\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\Process\Process;
use Symfony\Component\Yaml\Yaml;

/**
 * Tests the branch the deploy workflow hands to the deploy script.
 *
 * A tag push reports the tag name as the head branch, so the workflow clears
 * it and falls back to the default branch. Git allows a branch and a tag to
 * share a name, so the head branch is only cleared when a tag of that name
 * sits on the commit the run was triggered by.
 *
 * The step's shell is read out of the workflow and executed against a
 * purpose-built repository, so these assertions cover the script the runner
 * would execute rather than a re-implementation of it.
 *
 * phpcs:disable Drupal.Commenting.FunctionComment.Missing
 * phpcs:disable Drupal.Commenting.DocComment.MissingShort
 */
#[Group('p0')]
final class DeployWorkflowBranchTest extends UnitTestCase {

  protected const STEP = 'Deploy to Remote';

  protected const DEFAULT_BRANCH = 'main';

  protected const FEATURE_BRANCH = 'feature/x';

  protected const LIGHTWEIGHT_TAG = '1.0.0';

  protected const ANNOTATED_TAG = '2.0.0';

  #[DataProvider('dataProviderResolvedBranch')]
  public function testResolvedBranch(string $deploy_branch, string $head_branch, string $head_commit, string $expected): void {
    $repository = $this->createRepository();

    $actual = $this->runStep($repository, [
      'DEPLOY_BRANCH' => $deploy_branch,
      'HEAD_BRANCH' => $head_branch,
      'HEAD_SHA' => $this->resolveCommit($repository, $head_commit),
      'DEFAULT_BRANCH' => self::DEFAULT_BRANCH,
    ]);

    $this->assertSame($expected, $actual);
  }

  public static function dataProviderResolvedBranch(): \Iterator {
    yield 'branch push' => [
      'deploy_branch' => '',
      'head_branch' => self::FEATURE_BRANCH,
      'head_commit' => 'tip',
      'expected' => self::FEATURE_BRANCH,
    ];

    yield 'lightweight tag push' => [
      'deploy_branch' => '',
      'head_branch' => self::LIGHTWEIGHT_TAG,
      'head_commit' => 'tagged',
      'expected' => self::DEFAULT_BRANCH,
    ];

    yield 'annotated tag push' => [
      'deploy_branch' => '',
      'head_branch' => self::ANNOTATED_TAG,
      'head_commit' => 'tagged',
      'expected' => self::DEFAULT_BRANCH,
    ];

    yield 'branch sharing a name with a tag on another commit' => [
      'deploy_branch' => '',
      'head_branch' => self::LIGHTWEIGHT_TAG,
      'head_commit' => 'tip',
      'expected' => self::LIGHTWEIGHT_TAG,
    ];

    yield 'repository variable overrides a tag push' => [
      'deploy_branch' => 'custom',
      'head_branch' => self::LIGHTWEIGHT_TAG,
      'head_commit' => 'tagged',
      'expected' => 'custom',
    ];

    yield 'no head branch' => [
      'deploy_branch' => '',
      'head_branch' => '',
      'head_commit' => 'tip',
      'expected' => self::DEFAULT_BRANCH,
    ];

    yield 'head commit absent from the clone' => [
      'deploy_branch' => '',
      'head_branch' => self::LIGHTWEIGHT_TAG,
      'head_commit' => 'unknown',
      'expected' => self::LIGHTWEIGHT_TAG,
    ];
  }

  public function testStepTakesEveryValueFromEnvironment(): void {
    $this->assertStringNotContainsString('${{', self::stepScript(), sprintf('The "%s" step must read every value through `env:` so that its shell can be executed as it stands.', self::STEP));
  }

  /**
   * Run the deploy step against a repository and return the resolved branch.
   *
   * @param string $repository
   *   Directory of the repository to run in.
   * @param array<string, string> $environment
   *   Environment the workflow defines for the step.
   *
   * @return string
   *   The branch the step passed to the deploy script.
   */
  protected function runStep(string $repository, array $environment): string {
    $script = self::$tmp . '/step.sh';
    file_put_contents($script, self::stepScript());

    // GitHub Actions runs a `run` block as `bash -e {0}`.
    $process = new Process(['bash', '-e', $script], $repository, $environment + self::gitEnvironment());
    $process->run();

    if (!$process->isSuccessful()) {
      self::fail(sprintf("The deploy step failed:\n%s", $process->getErrorOutput()));
    }

    return trim($process->getOutput());
  }

  /**
   * Create a repository where a branch and a tag share a name.
   *
   * @return string
   *   Directory of the created repository.
   */
  protected function createRepository(): string {
    $repository = self::$tmp . '/repository';
    mkdir($repository . '/.devtools', 0755, TRUE);

    // The step ends by invoking the deploy script with the resolved branch in
    // the environment, so this stub reports it back.
    file_put_contents($repository . '/.devtools/deploy', "#!/usr/bin/env bash\nprintf '%s\\n' \"\${DEPLOY_BRANCH}\"\n");
    chmod($repository . '/.devtools/deploy', 0755);

    $this->git($repository, ['init', '--initial-branch=' . self::DEFAULT_BRANCH]);
    $this->git($repository, ['config', 'user.name', 'Test User']);
    $this->git($repository, ['config', 'user.email', 'test@example.com']);

    file_put_contents($repository . '/file.txt', 'first');
    $this->git($repository, ['add', '--all']);
    $this->git($repository, ['commit', '--message', 'First commit']);

    $this->git($repository, ['tag', self::LIGHTWEIGHT_TAG]);
    $this->git($repository, ['tag', '--annotate', self::ANNOTATED_TAG, '--message', 'Release']);

    file_put_contents($repository . '/file.txt', 'second');
    $this->git($repository, ['commit', '--all', '--message', 'Second commit']);

    $this->git($repository, ['branch', self::LIGHTWEIGHT_TAG]);
    $this->git($repository, ['branch', self::FEATURE_BRANCH]);

    return $repository;
  }

  /**
   * Resolve the commit that a run was triggered on.
   *
   * @param string $repository
   *   Directory of the repository to resolve in.
   * @param string $name
   *   Name of a commit of the repository built by ::createRepository().
   *
   * @return string
   *   The commit SHA.
   */
  protected function resolveCommit(string $repository, string $name): string {
    return match ($name) {
      'tagged' => $this->git($repository, ['rev-parse', self::DEFAULT_BRANCH . '~1']),
      'tip' => $this->git($repository, ['rev-parse', self::DEFAULT_BRANCH]),
      // A commit the deploy clone does not hold, as happens for a run
      // triggered from a fork.
      'unknown' => str_repeat('0', 40),
      default => self::fail(sprintf('Unknown commit "%s".', $name)),
    };
  }

  /**
   * Run a git command in a repository.
   *
   * @param string $repository
   *   Directory of the repository to run in.
   * @param array<int, string> $arguments
   *   Arguments for the command.
   *
   * @return string
   *   The trimmed standard output.
   */
  protected function git(string $repository, array $arguments): string {
    $process = new Process(array_merge(['git'], $arguments), $repository, self::gitEnvironment());
    $process->run();

    if (!$process->isSuccessful()) {
      self::fail(sprintf("git %s failed:\n%s", implode(' ', $arguments), $process->getErrorOutput()));
    }

    return trim($process->getOutput());
  }

  /**
   * Environment that detaches git from the configuration of the host.
   *
   * @return array<string, string>
   *   Environment variables.
   */
  protected static function gitEnvironment(): array {
    return [
      'GIT_CONFIG_GLOBAL' => '/dev/null',
      'GIT_CONFIG_SYSTEM' => '/dev/null',
    ];
  }

  /**
   * Read the shell of the deploy step out of the workflow.
   *
   * @return string
   *   The step's `run` script.
   */
  protected static function stepScript(): string {
    $path = dirname(__DIR__, 4) . '/.github/workflows/deploy.yml';
    $contents = file_get_contents($path);

    if ($contents === FALSE) {
      self::fail(sprintf('Unable to read %s.', $path));
    }

    $steps = self::child(self::child(self::child(Yaml::parse($contents), 'jobs'), 'deploy'), 'steps');

    if (!is_array($steps)) {
      self::fail(sprintf('The deploy job of %s declares no steps.', basename($path)));
    }

    foreach ($steps as $step) {
      if (self::child($step, 'name') !== self::STEP) {
        continue;
      }

      $script = self::child($step, 'run');

      if (!is_string($script)) {
        self::fail(sprintf('The "%s" step of %s runs no script.', self::STEP, basename($path)));
      }

      return $script;
    }

    self::fail(sprintf('%s has no "%s" step.', basename($path), self::STEP));
  }

  protected static function child(mixed $node, string $key): mixed {
    return is_array($node) && array_key_exists($key, $node) ? $node[$key] : NULL;
  }

}
