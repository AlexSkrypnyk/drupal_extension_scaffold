<?php

declare(strict_types=1);

namespace Scaffold\Tests\Traits;

use Composer\Console\Application;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\BufferedOutput;

trait ComposerTrait {

  protected function composerCreateProject(array|string $args = NULL): string {
    $args = $args ?? '';
    $args = is_array($args) ? $args : [$args];
    $args[] = $this->dirs->sut;
    $args = implode(' ', $args);

    return 'create-project --repository \'{"type": "path", "url": "' . $this->dirs->repo . '", "options": {"symlink": false}}\' alexskrypnyk/drupal_extension_scaffold="@dev" ' . $args;
  }

  /**
   * Runs a `composer` command.
   *
   * @param string $cmd
   *   The Composer command to execute (escaped as required)
   * @param string|null $cwd
   *   The current working directory to run the command from.
   * @param array $env
   *   Environment variables to define for the subprocess.
   *
   * @return string
   *   Standard output and standard error from the command.
   *
   * @throws \Exception
   */
  public function composerRun(string $cmd, ?string $cwd = NULL, array $env = []): string {
    $cwd = $cwd ?: $this->dirs->build;

    $env += [
      'DREVOPS_SCAFFOLD_VERSION' => '@dev',
    ];

    $this->envFromInput($env);

    chdir($cwd);

    $input = new StringInput($cmd);
    $output = new BufferedOutput();
    // $output->setVerbosity(ConsoleOutput::VERBOSITY_QUIET);
    $application = new Application();
    $application->setAutoExit(FALSE);

    $code = $application->run($input, $output);
    $output = $output->fetch();

    $this->envReset();

    if ($code != 0) {
      throw new \Exception("Fixtures::composerRun failed to set up fixtures.\n\nCommand: '{$cmd}'\nExit code: {$code}\nOutput: \n\n{$output}");
    }

    return $output;
  }

  protected function composerReadJson(?string $path = NULL): array {
    $path = $path ?: $this->dirs->sut . '/composer.json';
    $this->assertFileExists($path);

    $composerjson = file_get_contents($path);
    $this->assertIsString($composerjson);

    $data = json_decode($composerjson, TRUE);
    $this->assertIsArray($data);

    return $data;
  }
}
