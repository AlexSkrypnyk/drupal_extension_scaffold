<?php

declare(strict_types=1);

namespace Scaffold;

use Composer\Script\Event;

class Customizer {

  protected string $extenstion_name;
  protected string $extenstion_machine_name;
  protected string $extenstion_type;
  protected string $ci_provider;
  protected string $command_wrapper;

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
    string $extenstion_name,
    string $extenstion_machine_name,
    string $extenstion_type = 'module',
    string $ci_provider = 'gha',
    string $command_wrapper = 'ahoy') {

    $this->extenstion_name = $extenstion_name;
    $this->extenstion_machine_name = $extenstion_machine_name;
    $this->extenstion_type = $extenstion_type;
    $this->ci_provider = $ci_provider;
    $this->command_wrapper = $command_wrapper;
  }

  public function process() {
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
    $ci_provider = $this->ci_provider;
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
    $command_wrapper = $this->command_wrapper;
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
    $extension_name = $event->getIO()->ask('Name', 'Your Extenstion');
    $extension_machine_name = $event->getIO()->ask('Machine Name', 'your_extension');
    $extension_type = 'module';
    $extension_type = $event->getIO()->ask('Type: module or theme', $extension_type);
    $ci_provider = 'gha';
    $ci_provider = $event->getIO()->ask('CI Provider: GitHub Actions (gha) or CircleCI (circleci)', $ci_provider);
    $command_wrapper = 'ahoy';
    $command_wrapper = $event->getIO()->ask('Command wrapper: Ahoy (ahoy), Makefile (makefile), None (none)', $command_wrapper);

    $customizer = new static($extension_name, $extension_machine_name, $extension_type, $ci_provider, $command_wrapper);
    try {
      $customizer->process();
    } catch (\Exception $exception) {
      throw new \Exception(printf('Initialization is not completed. Error %s', $exception->getMessage()), $exception->getCode(), $exception);
    }
  }
}
