<?php

declare(strict_types=1);

namespace Scaffold;

use Composer\IO\IOInterface;
use Composer\Script\Event;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

class Customizer {

  protected Filesystem $fileSystem;
  protected IOInterface $io;
  protected string $workingDir;
  protected string $extensionName;
  protected string $extensionMachineName;
  protected string $extensionType;
  protected string $ciProvider;
  protected string $commandWrapper;

  /**
   * Construct.
   *
   * @param string $extension_name
   *   Extension name.
   * @param string $extension_machine_name
   *   Extension machine name.
   * @param string $extension_type
   *   Extension type: module or theme.
   * @param string $ci_provider
   *   CI Provider: gha or circleci
   * @param string $command_wrapper
   *   Command wrapper: ahoy, makefile or none.
   */
  public function __construct(
    Filesystem $filesystem,
    IOInterface $io,
    string $working_dir,
    string $extension_name,
    string $extension_machine_name,
    string $extension_type = 'module',
    string $ci_provider = 'gha',
    string $command_wrapper = 'ahoy') {

    $this->fileSystem = $filesystem;
    $this->workingDir = $working_dir;
    $this->io = $io;
    $this->extensionName = $extension_name;
    $this->extensionMachineName = $extension_machine_name;
    $this->extensionType = $extension_type;
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
    $this->io->write("Name                             : {$this->extensionName}");
    $this->io->write("Machine name                     : {$this->extensionMachineName}");
    $this->io->write("Type                             : {$this->extensionType}");
    $this->io->write("CI Provider                      : {$this->ciProvider}");
    $this->io->write("Command wrapper                  : {$this->commandWrapper}");
    $this->io->write("Working dir                      : {$this->workingDir}");
  }

  /**
   * Process README.md.
   */
  protected function processReadme(): void {
    $this->fileSystem->remove("$this->workingDir/README.md");
    $this->fileSystem->rename("$this->workingDir/README.dist.md", "$this->workingDir/README.md");
    $logo_url = sprintf(
      'https://placehold.jp/000000/ffffff/200x200.png?text=%s&css=%s',
      urlencode($this->extensionName),
      urlencode('{"border-radius":" 100px"}'),
    );
    $logo_data = file_get_contents($logo_url);
    if ($logo_data) {
      file_put_contents("$this->workingDir/logo.png", $logo_data);
    }
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
    $this->fileSystem->remove("$this->workingDir/.circleci");
  }

  /**
   * Remove GitHub Action (gha) CI provider.
   */
  protected function removeGhaCiProvider(): void {
    $this->fileSystem->remove("$this->workingDir/.github/workflows");
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
      case 'makefile':
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
    $this->fileSystem->remove("$this->workingDir/.ahoy.yml");
  }

  /**
   * Remove 'Make' command wrapper.
   */
  protected function removeMakeCommandWrapper(): void {
    $this->fileSystem->remove("$this->workingDir/Makefile");
  }

  /**
   * @throws \Exception
   */
  public static function main(Event $event): void {
    $io = $event->getIO();
    $extension_name = $io->ask('Name: ', 'Your Extension');
    $extension_machine_name = $io->ask('Machine Name: ', 'your_extension');
    $extension_type = 'module';
    $extension_type = $io->ask('Type: module or theme: ', $extension_type);
    $ci_provider = 'gha';
    $ci_provider = $io->ask('CI Provider: GitHub Actions (gha) or CircleCI (circleci): ', $ci_provider);
    $command_wrapper = 'ahoy';
    $command_wrapper = $io->ask('Command wrapper: Ahoy (ahoy), Makefile (makefile), None (none): ', $command_wrapper);
    $working_dir = Path::makeAbsolute('..', __DIR__);
    $fileSystem = new Filesystem();
    $customizer = new static(
      $fileSystem,
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

  /**
   * Convert a string to specific type.
   *
   * @throws \Exception
   */
  public static function convertString(string $string, string $type = 'function_name'): string {
    switch ($type) {
      case 'file_name':
      case 'route_path':
      case 'deployment_id':
      case 'function_name':
      case 'ui_id':
      case 'cli_command':
        $string_out = str_replace(' ', '_', $string);
        $string_out = strtolower($string_out);
        break;
      case 'domain_name':
      case 'package_namespace':
        $string_out = str_replace([' ', '-'], ['_', ''], $string);
        $string_out = strtolower($string_out);
        break;
      case 'namespace':
      case 'class_name':
        $string_out = str_replace(['-', ' '], ['_', ''], $string);
        $string_array = explode(' ', $string_out);
        $new_string_array = [];
        foreach ($string_array as $str) {
          if (!empty(trim($str))) {
            $new_string_array[] = ucfirst($str);
          }
        }
        $string_out = implode('', $new_string_array);
        break;
      case 'package_name':
        $string_out = str_replace(' ', '-', $string);
        $string_out = strtolower($string_out);
        break;
      case 'log_entry':
      case 'code_comment_title':
        $string_out = $string;
        break;
      default:
        throw new \Exception("Convert string does not support type $type.");
    }

    return $string_out;
  }
}
