<?php

declare(strict_types=1);

namespace Scaffold;

use Composer\IO\IOInterface;
use Composer\Script\Event;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Finder\Finder;

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
    $this->io->notice('            Summary');
    $this->io->notice('---------------------------------');
    $this->io->notice("Name                             : {$this->extensionName}");
    $this->io->notice("Machine name                     : {$this->extensionMachineName}");
    $this->io->notice("Type                             : {$this->extensionType}");
    $this->io->notice("CI Provider                      : {$this->ciProvider}");
    $this->io->notice("Command wrapper                  : {$this->commandWrapper}");
    $this->io->notice("Working dir                      : {$this->workingDir}");
  }

  /**
   * Process README.md.
   */
  protected function processReadme(): void {
    $this->io->notice('Processing README.');
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
   * @throws \Exception
   */
  protected function processInternalReplacement(): void {
    $this->io->notice('Processing internal replacement.');

    $extension_machine_name_class = self::convertString($this->extensionMachineName, 'class_name');
    self::replaceStringInFilesInDirectory('YourExtension', $extension_machine_name_class, $this->workingDir);
    self::replaceStringInFilesInDirectory('AlexSkrypnyk', $extension_machine_name_class, $this->workingDir);

    self::replaceStringInFilesInDirectory('YourNamespace', $this->extensionMachineName, $this->workingDir);
    self::replaceStringInFilesInDirectory('yournamespace', $this->extensionMachineName, $this->workingDir);
    self::replaceStringInFilesInDirectory('alexskrypnyk', $this->extensionMachineName, $this->workingDir);
    self::replaceStringInFilesInDirectory('yourproject', $this->extensionMachineName, $this->workingDir);
    self::replaceStringInFilesInDirectory('Your+Extension', $this->extensionMachineName, $this->workingDir);
    self::replaceStringInFilesInDirectory('your_extension', $this->extensionMachineName, $this->workingDir);
    self::replaceStringInFilesInDirectory('drupal_extension_scaffold', $this->extensionMachineName, $this->workingDir);
    self::replaceStringInFilesInDirectory('[EXTENSION_NAME]', $this->extensionMachineName, $this->workingDir);
    self::replaceStringInFilesInDirectory(
      'Provides your_extension functionality.',
      "Provides $this->extensionMachineName functionality.",
      $this->workingDir,
    );
    self::replaceStringInFilesInDirectory(
      'Drupal module scaffold FE example used for template testing',
      "Provides $this->extensionMachineName functionality.",
      $this->workingDir,
    );

    self::replaceStringInFilesInDirectory('Drupal extension scaffold', $this->extensionName, $this->workingDir);
    self::replaceStringInFilesInDirectory('Yourproject', $this->extensionName, $this->workingDir);
    self::replaceStringInFilesInDirectory('Your Extension', $this->extensionName, $this->workingDir);
    self::replaceStringInFilesInDirectory('your extension', $this->extensionName, $this->workingDir);

    self::replaceStringInFilesInDirectory('drupal-module', "drupal-$this->extensionType", $this->workingDir);
    self::replaceStringInFilesInDirectory('type: module', "type: $this->extensionType", $this->workingDir);
  }

  /**
   * Remove CI provider depends on CI provider selected.
   */
  protected function removeCiProvider(): void {
    $this->io->notice('Processing remove CI Provider.');
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
    $this->io->notice('Processing remove Command Wrapper.');
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
    $default_extension_machine_name = self::convertString($extension_name, 'file_name');
    $extension_machine_name = $io->ask("Machine Name: [$default_extension_machine_name]", $default_extension_machine_name);
    $default_extension_type = 'module';
    $extension_type = $io->ask("Type: module or theme: [$default_extension_type]", $default_extension_type);
    $default_ci_provider = 'gha';
    $ci_provider = $io->ask("CI Provider: GitHub Actions (gha) or CircleCI (circleci): [$default_ci_provider]", $default_ci_provider);
    $default_command_wrapper = 'ahoy';
    $command_wrapper = $io->ask("Command wrapper: Ahoy (ahoy), Makefile (makefile), None (none): [$default_command_wrapper]", $default_command_wrapper);

    if (!$extension_name) {
      throw new \Exception('Name is required.');
    }
    if (!$extension_machine_name) {
      throw new \Exception('Machine name is required.');
    }
    if (!in_array($extension_type, ['theme', 'module'])) {
      throw new \Exception('Extension type is required or invalid.');
    }
    if (!in_array($ci_provider, ['gha', 'circleci'])) {
      throw new \Exception('CI provider is required or invalid.');
    }
    if (!in_array($command_wrapper, ['ahoy', 'makefile', 'none'])) {
      throw new \Exception('Command wrapper is required or invalid.');
    }

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
        $string_out = str_replace(['-', ' '], ['_', ' '], $string);
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

  /**
   * Replace string in files in a directory.
   *
   * @param string $string_search
   *   String to search.
   * @param string $string_replace
   *   String to replace.
   * @param string $directory
   *   Directory.
   */
  public static function replaceStringInFilesInDirectory(string $string_search, string $string_replace, string $directory): void {
    $finder = new Finder();
    $finder
      ->files()
      ->contains($string_search)
      ->in($directory);
    if ($finder->hasResults()) {
      foreach ($finder as $file) {
        $file_content = $file->getContents();
        $new_file_content = str_replace($string_search, $string_replace, $file_content);
        file_put_contents($file->getRealPath(), $new_file_content);
      }
    }
  }
}
