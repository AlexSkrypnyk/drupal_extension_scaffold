<?php

declare(strict_types=1);

namespace Scaffold;

use Composer\IO\IOInterface;
use Composer\Script\Event;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Finder\Finder;

/**
 * Class to setup drupal scaffold extension right way.
 */
class Customizer {

  /**
   * File system.
   */
  protected Filesystem $fileSystem;

  /**
   * IO composer.
   */
  protected IOInterface $io;

  /**
   * The directory to create the drupal extension scaffold.
   */
  protected string $workingDir;

  /**
   * Drupal extension name.
   */
  protected string $extensionName;

  /**
   * Drupal extension machine name.
   */
  protected string $extensionMachineName;

  /**
   * Drupal extension type: module or theme.
   */
  protected string $extensionType;

  /**
   * CI provider: gha or  circleci.
   */
  protected string $ciProvider;

  /**
   * Command wrapper: ahoy, makefile or none.
   */
  protected string $commandWrapper;

  /**
   * Construct.
   *
   * @param \Symfony\Component\Filesystem\Filesystem $filesystem
   *   File system.
   * @param \Composer\IO\IOInterface $io
   *   IO.
   * @param string $working_dir
   *   Working dir.
   * @param string $extension_name
   *   Extension name.
   * @param string $extension_machine_name
   *   Extension machine name.
   * @param string $extension_type
   *   Extension type: module or theme.
   * @param string $ci_provider
   *   CI Provider: gha or circleci.
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
    string $command_wrapper = 'ahoy',
  ) {
    $this->fileSystem = $filesystem;
    $this->workingDir = $working_dir;
    $this->io = $io;
    $this->extensionName = $extension_name;
    $this->extensionMachineName = $extension_machine_name;
    $this->extensionType = $extension_type;
    $this->ciProvider = $ci_provider;
    $this->commandWrapper = $command_wrapper;
  }

  /**
   * Process.
   *
   * @throws \Exception
   */
  public function process(): void {
    // Display summary.
    $this->displaySummary();
    // Remove CI Provider.
    $this->removeCiProvider();
    // Remove command wrapper.
    $this->removeCommandWrapper();
    // Process README.
    $this->processReadme();
    // Process Composer.
    $this->processComposer();
    // Process internal replacement.
    $this->processInternalReplacement();
  }

  /**
   * Display summary input.
   */
  protected function displaySummary(): void {
    $this->io->notice('            Summary');
    $this->io->notice('---------------------------------');
    $this->io->notice('Name                             : ' . $this->extensionName);
    $this->io->notice('Machine name                     : ' . $this->extensionMachineName);
    $this->io->notice('Type                             : ' . $this->extensionType);
    $this->io->notice('CI Provider                      : ' . $this->ciProvider);
    $this->io->notice('Command wrapper                  : ' . $this->commandWrapper);
    $this->io->notice('Working dir                      : ' . $this->workingDir);
  }

  /**
   * Process README.md.
   */
  protected function processReadme(): void {
    $this->io->notice('Processing README.');
    $this->fileSystem->remove($this->workingDir . '/README.md');
    $this->fileSystem->rename($this->workingDir . '/README.dist.md', $this->workingDir . '/README.md');
    $logo_url = sprintf(
      'https://placehold.jp/000000/ffffff/200x200.png?text=%s&css=%s',
      urlencode($this->extensionName),
      urlencode('{"border-radius":" 100px"}'),
    );
    $logo_data = file_get_contents($logo_url);
    if ($logo_data) {
      file_put_contents($this->workingDir . '/logo.png', $logo_data);
    }
  }

  /**
   * Process composer scaffold.
   */
  protected function processComposer(): void {
    $this->fileSystem->remove($this->workingDir . '/composer.json');
    $this->fileSystem->remove($this->workingDir . '/composer.lock');
    $this->fileSystem->remove($this->workingDir . '/vendor');
    $this->fileSystem->rename($this->workingDir . '/composer.dist.json', $this->workingDir . '/composer.json');
  }

  /**
   * Internal process to replace scaffold string and remove scaffold files.
   *
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
      sprintf('Provides %s functionality.', $this->extensionMachineName),
      $this->workingDir,
    );
    self::replaceStringInFilesInDirectory(
      'Drupal module scaffold FE example used for template testing',
      sprintf('Provides %s functionality.', $this->extensionMachineName),
      $this->workingDir,
    );
    self::replaceStringInFilesInDirectory('Drupal extension scaffold', $this->extensionName, $this->workingDir);
    self::replaceStringInFilesInDirectory('Yourproject', $this->extensionName, $this->workingDir);
    self::replaceStringInFilesInDirectory('Your Extension', $this->extensionName, $this->workingDir);
    self::replaceStringInFilesInDirectory('your extension', $this->extensionName, $this->workingDir);
    self::replaceStringInFilesInDirectory('drupal-module', 'drupal-' . $this->extensionType, $this->workingDir);
    self::replaceStringInFilesInDirectory('type: module', 'type: ' . $this->extensionType, $this->workingDir);
    self::replaceStringInFilesInDirectory('type: module', 'type: ' . $this->extensionType, $this->workingDir);

    self::replaceStringInFile('# Uncomment the lines below in your project.', '', $this->workingDir . '/.gitattributes');
    self::replaceStringInFile('# Remove the lines below in your project.', '', $this->workingDir . '/.gitattributes');
    self::replaceStringInFile('.github/FUNDING.yml export-ignore', '', $this->workingDir . '/.gitattributes');
    self::replaceStringInFile('LICENSE             export-ignore', '', $this->workingDir . '/.gitattributes');
    self::replaceStringInFile('# .ahoy.yml          export-ignore', '.ahoy.yml          export-ignore', $this->workingDir . '/.gitattributes');
    self::replaceStringInFile('# .circleci          export-ignore', '.circleci          export-ignore', $this->workingDir . '/.gitattributes');
    self::replaceStringInFile('# .devtools          export-ignore', '.devtools          export-ignore', $this->workingDir . '/.gitattributes');
    self::replaceStringInFile('# .editorconfig      export-ignore', '.editorconfig      export-ignore', $this->workingDir . '/.gitattributes');
    self::replaceStringInFile('# .gitattributes     export-ignore', '.gitattributes     export-ignore', $this->workingDir . '/.gitattributes');
    self::replaceStringInFile('# .github            export-ignore', '.github            export-ignore', $this->workingDir . '/.gitattributes');
    self::replaceStringInFile('# .gitignore         export-ignore', '.gitignore         export-ignore', $this->workingDir . '/.gitattributes');
    self::replaceStringInFile('# .twig-cs-fixer.php export-ignore', '.twig-cs-fixer.php export-ignore', $this->workingDir . '/.gitattributes');
    self::replaceStringInFile('# Makefile           export-ignore', 'Makefile           export-ignore', $this->workingDir . '/.gitattributes');
    self::replaceStringInFile('# composer.dev.json  export-ignore', 'composer.dev.json  export-ignore', $this->workingDir . '/.gitattributes');
    self::replaceStringInFile('# phpcs.xml          export-ignore', 'phpcs.xml          export-ignore', $this->workingDir . '/.gitattributes');
    self::replaceStringInFile('# phpmd.xml          export-ignore', 'phpmd.xml          export-ignore', $this->workingDir . '/.gitattributes');
    self::replaceStringInFile('# phpstan.neon       export-ignore', 'phpstan.neon       export-ignore', $this->workingDir . '/.gitattributes');
    self::replaceStringInFile('# rector.php         export-ignore', 'rector.php         export-ignore', $this->workingDir . '/.gitattributes');
    self::replaceStringInFile('# renovate.json      export-ignore', 'renovate.json      export-ignore', $this->workingDir . '/.gitattributes');
    self::replaceStringInFile('# tests              export-ignore', 'tests              export-ignore', $this->workingDir . '/.gitattributes');

    $this->fileSystem->rename($this->workingDir . '/your_extension.info.yml', sprintf('%s/%s.info.yml', $this->workingDir, $this->extensionMachineName));
    $this->fileSystem->rename($this->workingDir . '/your_extension.install', sprintf('%s/%s.install', $this->workingDir, $this->extensionMachineName));
    $this->fileSystem->rename($this->workingDir . '/your_extension.links.menu.yml', sprintf('%s/%s.links.menu.yml', $this->workingDir, $this->extensionMachineName));
    $this->fileSystem->rename($this->workingDir . '/your_extension.module', sprintf('%s/%s.module', $this->workingDir, $this->extensionMachineName));
    $this->fileSystem->rename($this->workingDir . '/your_extension.routing.yml', sprintf('%s/%s.routing.yml', $this->workingDir, $this->extensionMachineName));
    $this->fileSystem->rename($this->workingDir . '/your_extension.services.yml', sprintf('%s/%s.services.yml', $this->workingDir, $this->extensionMachineName));
    $this->fileSystem->rename($this->workingDir . '/config/schema/your_extension.schema.yml', sprintf('%s/config/schema/%s.schema.yml', $this->workingDir, $this->extensionMachineName));
    $this->fileSystem->rename($this->workingDir . '/src/Form/YourExtensionForm.php', sprintf('%s/src/Form/%sForm.php', $this->workingDir, $extension_machine_name_class));
    $this->fileSystem->rename($this->workingDir . '/src/YourExtensionService.php', sprintf('%s/src/%sService.php', $this->workingDir, $extension_machine_name_class));
    $this->fileSystem->rename($this->workingDir . '/tests/src/Unit/YourExtensionServiceUnitTest.php', sprintf('%s/tests/src/Unit/%sServiceUnitTest.php', $this->workingDir, $extension_machine_name_class));
    $this->fileSystem->rename($this->workingDir . '/tests/src/Kernel/YourExtensionServiceKernelTest.php', sprintf('%s/tests/src/Kernel/%sServiceKernelTest.php', $this->workingDir, $extension_machine_name_class));
    $this->fileSystem->rename($this->workingDir . '/tests/src/Functional/YourExtensionFunctionalTest.php', sprintf('%s/tests/src/Functional/%sFunctionalTest.php', $this->workingDir, $extension_machine_name_class));

    $this->fileSystem->remove($this->workingDir . '/LICENSE');
    $this->fileSystem->remove($this->workingDir . '/tests/scaffold');
    $this->fileSystem->remove($this->workingDir . '/.scaffold');
    $finder = Finder::create();
    $finder
      ->files()
      ->in($this->workingDir . '/.github/workflows')
      ->name('scaffold*.yml');
    if ($finder->hasResults()) {
      foreach ($finder as $file) {
        $this->fileSystem->remove($file->getRealPath());
      }
    }

    if ($this->extensionType === 'theme') {
      $this->fileSystem->remove($this->workingDir . '/test');
      $this->fileSystem->appendToFile(sprintf('%s/%s.info.yml', $this->workingDir, $this->extensionMachineName), 'base theme: false');
    }
  }

  /**
   * Remove CI provider depends on CI provider selected.
   */
  protected function removeCiProvider(): void {
    $this->io->notice('Processing remove CI Provider.');
    $ci_provider = $this->ciProvider;
    if ($ci_provider === 'gha') {
      $this->removeCircleciCiProvider();
    }
    else {
      $this->removeGhaCiProvider();
    }
  }

  /**
   * Remove CircleCi (circleci) CI provider.
   */
  protected function removeCircleciCiProvider(): void {
    $this->fileSystem->remove($this->workingDir . '/.circleci');
  }

  /**
   * Remove GitHub Action (gha) CI provider.
   */
  protected function removeGhaCiProvider(): void {
    $this->fileSystem->remove($this->workingDir . '/.github/workflows');
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
    $this->fileSystem->remove($this->workingDir . '/.ahoy.yml');
  }

  /**
   * Remove 'Make' command wrapper.
   */
  protected function removeMakeCommandWrapper(): void {
    $this->fileSystem->remove($this->workingDir . '/Makefile');
  }

  /**
   * @throws \Exception
   */
  public static function main(Event $event): void {
    $io = $event->getIO();

    $io->notice('Please follow the prompts to adjust your extension configuration');

    $extension_name = $io->ask('Name: ', 'Your Extension');
    $default_extension_machine_name = self::convertString($extension_name, 'file_name');
    $extension_machine_name = $io->ask(sprintf('Machine Name: [%s]: ', $default_extension_machine_name), $default_extension_machine_name);
    $default_extension_type = 'module';
    $extension_type = $io->ask(sprintf('Type: module or theme: [%s]: ', $default_extension_type), $default_extension_type);
    $default_ci_provider = 'gha';
    $ci_provider = $io->ask(sprintf('CI Provider: GitHub Actions (gha) or CircleCI (circleci): [%s]: ', $default_ci_provider), $default_ci_provider);
    $default_command_wrapper = 'ahoy';
    $command_wrapper = $io->ask(sprintf('Command wrapper: Ahoy (ahoy), Makefile (makefile), None (none): [%s]: ', $default_command_wrapper), $default_command_wrapper);

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

    // @phpstan-ignore-next-line
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
    }
    catch (\Exception $exception) {
      throw new \Exception(sprintf('Initialization is not completed. Error %s', $exception->getMessage()), $exception->getCode(), $exception);
    }

    $io->notice('Initialization complete.');
  }

  /**
   * Convert a string to specific type.
   *
   * @throws \Exception
   *
   * @SuppressWarnings(PHPMD.CyclomaticComplexity)
   */
  public static function convertString(string $string, string $type = 'file_name'): string {
    switch ($type) {
      case 'file_name':
        $string_out = str_replace(' ', '_', $string);
        $string_out = strtolower($string_out);
        break;

      case 'package_namespace':
        $string_out = str_replace([' ', '-'], ['_', '_'], $string);
        $string_out = strtolower($string_out);
        break;

      case 'namespace':
      case 'class_name':
        $string_out = str_replace(['-', '_'], [' ', ' '], $string);
        $string_array = explode(' ', $string_out);
        $new_string_array = [];
        foreach ($string_array as $str) {
          if (!empty(trim($str))) {
            $new_string_array[] = ucfirst($str);
          }
        }
        $string_out = implode('', $new_string_array);
        break;

      default:
        throw new \Exception(sprintf('Convert string does not support type %s.', $type));
    }

    return $string_out;
  }

  /**
   * Replace string in files in a directory.
   *
   * @param string|string[] $string_search
   *   String to search.
   * @param string|string[] $string_replace
   *   String to replace.
   * @param string $directory
   *   Directory.
   */
  public static function replaceStringInFilesInDirectory(string|array $string_search, string|array $string_replace, string $directory): void {
    $finder = new Finder();
    $finder
      ->files()
      ->contains($string_search)
      ->in($directory);
    if ($finder->hasResults()) {
      foreach ($finder as $file) {
        self::replaceStringInFile($string_search, $string_replace, $file->getRealPath());
      }
    }
  }

  /**
   * Replace string in a file.
   *
   * @param string|string[] $string_search
   *   String to search.
   * @param string|string[] $string_replace
   *   String to replace.
   * @param string $file_path
   *   File path.
   */
  public static function replaceStringInFile(string|array $string_search, string|array $string_replace, string $file_path): void {
    $file_content = file_get_contents($file_path);
    if (!empty($file_content)) {
      $new_file_content = str_replace($string_search, $string_replace, $file_content);
      file_put_contents($file_path, $new_file_content);
    }
  }

}
