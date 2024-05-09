<?php

declare(strict_types=1);

namespace Scaffold;

use Composer\IO\IOInterface;
use Composer\Script\Event;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

class Customizer {

  protected IOInterface $io;
  protected string $workingDir;
  protected string $extenstionName;
  protected string $extenstionMachineName;
  protected string $extenstionType;
  protected string $ciProvider;
  protected string $commandWrapper;

  /**
   * Construct.
   *
   * @param string $extenstion_name
   *   Extension name.
   * @param string $extenstion_machine_name
   *   Extension machine name.
   * @param string $extenstion_type
   *   Extenstion type: module or theme.
   * @param string $ci_provider
   *   CI Provider: gha or circleci
   * @param string $command_wrapper
   *   Command wrapper: ahoy, makefile or none.
   */
  public function __construct(
    IOInterface $io,
    string $working_dir,
    string $extenstion_name,
    string $extenstion_machine_name,
    string $extenstion_type = 'module',
    string $ci_provider = 'gha',
    string $command_wrapper = 'ahoy') {

    $this->workingDir = $working_dir;
    $this->io = $io;
    $this->extenstionName = $extenstion_name;
    $this->extenstionMachineName = $extenstion_machine_name;
    $this->extenstionType = $extenstion_type;
    $this->ciProvider = $ci_provider;
    $this->commandWrapper = $command_wrapper;
  }

  public function process() {
    // Display summary.
    $this->displaySummary();
    // Remove CI Provider.
    $this->removeCiProvider();
    // Remove command wrapper.
    $this->removeCommandWrapper();
    // Process README.
    $this->processReadme();
    // Process internal replacement.
    $this->processInternalReplacement();
  }

  /**
   * Display summary input.
   */
  protected function displaySummary(): void {
    $this->io->write('            Summary');
    $this->io->write('---------------------------------');
    $this->io->write("Name                             : {$this->extenstionName}");
    $this->io->write("Machine name                     : {$this->extenstionMachineName}");
    $this->io->write("Type                             : {$this->extenstionType}");
    $this->io->write("CI Provider                      : {$this->ciProvider}");
    $this->io->write("Command wrapper                  : {$this->commandWrapper}");
    $this->io->write("Working dir                      : {$this->workingDir}");
  }

  /**
   * Process README.md.
   */
  protected function processReadme(): void {

  }

  /**
   * Internal process to replace scaffold string and remove scaffold files.
   */
  protected function processInternalReplacement(): void {

  }

  /**
   * Remove CI provider depends on CI provider selected.
   */
  protected function removeCiProvider(): void {
    $ci_provider = $this->ciProvider;
    if ($ci_provider === 'gha') {
      $this->removeCircleciCiProvider();
    }else {
      $this->removeGhaCiProvider();
    }
  }

  /**
   * Remove CircleCi (circleci) CI provider.
   */
  protected function removeCircleciCiProvider(): void {

  }

  /**
   * Remove Github Action (gha) CI provider.
   */
  protected function removeGhaCiProvider(): void {

  }

  /**
   * Remove command wrappers depends on command wrapper selected.
   */
  protected function removeCommandWrapper(): void {
    $command_wrapper = $this->commandWrapper;
    switch ($command_wrapper) {
      case 'ahoy':
        $this->removeMakeCommandWrapper();
        break;
      case 'make':
        $this->removeAhoyCommandWrapper();
        break;
      default:
        $this->removeAhoyCommandWrapper();
        $this->removeMakeCommandWrapper();
        break;
    }
  }

  /**
   * Remove 'Ahoy' command wrapper.
   */
  protected function removeAhoyCommandWrapper(): void {

  }

  /**
   * Remove 'Make' command wrapper.
   */
  protected function removeMakeCommandWrapper(): void {

  }

  public static function main(Event $event) {
    $io = $event->getIO();
    $extension_name = $io->ask('Name: ', 'Your Extenstion');
    $extension_machine_name = $io->ask('Machine Name: ', 'your_extension');
    $extension_type = 'module';
    $extension_type = $io->ask('Type: module or theme: ', $extension_type);
    $ci_provider = 'gha';
    $ci_provider = $io->ask('CI Provider: GitHub Actions (gha) or CircleCI (circleci): ', $ci_provider);
    $command_wrapper = 'ahoy';
    $command_wrapper = $io->ask('Command wrapper: Ahoy (ahoy), Makefile (makefile), None (none): ', $command_wrapper);
    $working_dir = Path::makeAbsolute('..', __DIR__);

    $customizer = new static(
      $io,
      $working_dir,
      $extension_name,
      $extension_machine_name,
      $extension_type,
      $ci_provider,
      $command_wrapper
    );

    try {
      $customizer->process();
    } catch (\Exception $exception) {
      throw new \Exception(sprintf('Initialization is not completed. Error %s', $exception->getMessage()), $exception->getCode(), $exception);
    }
  }
}
