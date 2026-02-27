#!/usr/bin/env php
<?php

/**
 * @file
 * Adjust project repository based on user input.
 *
 * Environment variables:
 * - SCRIPT_QUIET: Set to '1' to suppress verbose messages.
 * - SCRIPT_RUN_SKIP: Set to '1' to skip running of the script. Useful when
 *   unit-testing or requiring this file from other files.
 *
 * Usage:
 * @code
 * php init.php
 * php init.php "Extension Name" extension_machine_name module gha ahoy
 * @endcode
 */

declare(strict_types=1);

/**
 * Main functionality.
 *
 * @param array<string> $argv
 *   Array of arguments.
 * @param int $argc
 *   Number of arguments.
 */
function main(array $argv, int $argc): void {
  if (array_intersect(['help', '--help', '-h', '-?'], $argv)) {
    print_help();

    return;
  }

  $extension_name = $argv[1] ?? '';
  $extension_machine_name = $argv[2] ?? '';
  $extension_type = $argv[3] ?? '';
  $ci_provider = $argv[4] ?? '';
  $command_wrapper = $argv[5] ?? '';

  verbose(PHP_EOL);
  verbose(color('Welcome to the Drupal Extension Scaffold init script', 'cyan') . PHP_EOL);
  verbose(color('Please follow the prompts to adjust your extension configuration', 'cyan') . PHP_EOL);
  verbose(PHP_EOL);

  if ($extension_name === '') {
    $extension_name = ask('Name');
  }

  $extension_machine_name_default = convert_string($extension_name, 'file_name');
  if ($extension_machine_name === '') {
    $extension_machine_name = ask('Machine name', $extension_machine_name_default);
  }

  if ($extension_type === '') {
    $extension_type = ask('Type: module or theme', 'module', ['module', 'theme']);
  }

  if ($ci_provider === '') {
    $ci_provider = ask('CI Provider: GitHub Actions (gha) or CircleCI (circleci)', 'gha', ['gha', 'circleci']);
  }

  if ($command_wrapper === '') {
    $command_wrapper = ask('Command wrapper: Ahoy (ahoy), Makefile (makefile), None (none)', 'ahoy', [
      'ahoy',
      'makefile',
      'none',
    ]);
  }

  $remove_self = ask_yesno('Remove this script');

  verbose(PHP_EOL);
  verbose('            Summary' . PHP_EOL);
  verbose('---------------------------------' . PHP_EOL);
  verbose('Name               : %s' . PHP_EOL, $extension_name);
  verbose('Machine name       : %s' . PHP_EOL, $extension_machine_name);
  verbose('Type               : %s' . PHP_EOL, $extension_type);
  verbose('CI Provider        : %s' . PHP_EOL, $ci_provider);
  verbose('Command wrapper    : %s' . PHP_EOL, $command_wrapper);
  verbose('Remove this script : %s' . PHP_EOL, $remove_self);
  verbose('---------------------------------' . PHP_EOL);
  verbose(PHP_EOL);

  $should_proceed = ask_yesno('Proceed with project init');

  if ($should_proceed !== 'y') {
    throw new \Exception('Aborting.');
  }

  process($extension_name, $extension_machine_name, $extension_type, $ci_provider, $command_wrapper, $remove_self);
}

/**
 * Process the project initialization.
 *
 * @param string $extension_name
 *   The human-readable extension name.
 * @param string $extension_machine_name
 *   The machine name of the extension.
 * @param string $extension_type
 *   The extension type (module or theme).
 * @param string $ci_provider
 *   The CI provider (gha or circleci).
 * @param string $command_wrapper
 *   The command wrapper (ahoy, makefile, or none).
 * @param string $remove_self
 *   Whether to remove this script ('y' or 'n').
 */
function process(string $extension_name, string $extension_machine_name, string $extension_type, string $ci_provider, string $command_wrapper, string $remove_self): void {
  // Validate required values.
  if ($extension_name === '') {
    throw new \Exception('Name is required.');
  }
  if ($extension_machine_name === '') {
    throw new \Exception('Machine name is required.');
  }
  if ($extension_type === '') {
    throw new \Exception('Type is required.');
  }
  if ($ci_provider === '') {
    throw new \Exception('CI provider is required.');
  }
  if ($command_wrapper === '') {
    throw new \Exception('Command wrapper is required.');
  }

  // Remove unwanted CI provider.
  if ($ci_provider === 'circleci') {
    remove_dir('.github/workflows');
  }
  else {
    remove_dir('.circleci');
  }

  // Remove unwanted command wrapper.
  if ($command_wrapper === 'ahoy') {
    @unlink('Makefile');
  }
  elseif ($command_wrapper === 'makefile') {
    @unlink('.ahoy.yml');
  }
  else {
    @unlink('.ahoy.yml');
    @unlink('Makefile');
  }

  process_readme($extension_name);

  process_internal($extension_name, $extension_machine_name, $extension_type);

  if ($remove_self !== 'n') {
    @unlink(__FILE__);
  }

  verbose(PHP_EOL);
  verbose('Initialization complete.' . PHP_EOL);
}

/**
 * Print help.
 */
function print_help(): void {
  $script_name = basename(__FILE__);
  $out = <<<EOF
Adjust project repository based on user input.
-----------------------------------------------

Arguments:
  name                  Extension name.
  machine_name          Extension machine name.
  extension_type        Extension type: module or theme.
  ci_provider           CI provider: gha or circleci.
  command_wrapper       Command wrapper: ahoy, makefile, or none.

Options:
  --help                This help.

Examples:
  Interactive:
    php {$script_name}

  Silent:
    php {$script_name} "Extension Name" extension_machine_name module gha ahoy

EOF;
  verbose($out);
}

/**
 * Process README file and download placeholder logo.
 *
 * @param string $extension_name
 *   The human-readable extension name.
 */
function process_readme(string $extension_name): void {
  @rename('README.dist.md', 'README.md');

  $url = 'https://placehold.jp/000000/ffffff/200x200.png?text=' . str_replace(' ', '+', $extension_name) . '&css=%7B%22border-radius%22%3A%22%20100px%22%7D';
  $logo = @file_get_contents($url);
  if ($logo !== FALSE && $logo !== '') {
    file_put_contents('logo.png', $logo);
  }
  @unlink('logo.tmp.png');
}

/**
 * Process internal replacements, renames, and cleanups.
 *
 * @param string $extension_name
 *   The human-readable extension name.
 * @param string $extension_machine_name
 *   The machine name of the extension.
 * @param string $extension_type
 *   The extension type (module or theme).
 */
function process_internal(string $extension_name, string $extension_machine_name, string $extension_type): void {
  $extension_machine_name_class = convert_string($extension_machine_name, 'class_name');

  replace_string_content('YourNamespace', $extension_machine_name);
  replace_string_content('yournamespace', $extension_machine_name);
  replace_string_content('AlexSkrypnyk', $extension_machine_name);
  replace_string_content('alexskrypnyk', $extension_machine_name);
  replace_string_content('yourproject', $extension_machine_name);
  replace_string_content('Yourproject logo', $extension_name . ' logo');
  replace_string_content('Your Extension', $extension_name);
  replace_string_content('your extension', $extension_name);
  replace_string_content('Your+Extension', $extension_machine_name);
  replace_string_content('your_extension', $extension_machine_name);
  replace_string_content('your-extension', $extension_machine_name);
  replace_string_content('YourExtension', $extension_machine_name_class);
  replace_string_content('Provides your_extension functionality.', 'Provides ' . $extension_machine_name . ' functionality.');
  replace_string_content('drupal-module', 'drupal-' . $extension_type);
  replace_string_content('Drupal module scaffold FE example used for template testing', 'Provides ' . $extension_machine_name . ' functionality.');
  replace_string_content('Drupal extension scaffold', $extension_name);
  replace_string_content('drupal_extension_scaffold', $extension_machine_name);
  replace_string_content('type: module', 'type: ' . $extension_type);
  replace_string_content('[EXTENSION_NAME]', $extension_machine_name);

  remove_string_content('# Uncomment the lines below in your project.');
  uncomment_line('.gitattributes', '.ahoy.yml');
  uncomment_line('.gitattributes', '.circleci');
  uncomment_line('.gitattributes', '.devtools');
  uncomment_line('.gitattributes', '.editorconfig');
  uncomment_line('.gitattributes', '.gitattributes');
  uncomment_line('.gitattributes', '.github');
  uncomment_line('.gitattributes', '.gitignore');
  uncomment_line('.gitattributes', '.skip_npm_build');
  uncomment_line('.gitattributes', '.twig-cs-fixer.php');
  uncomment_line('.gitattributes', 'Makefile');
  uncomment_line('.gitattributes', 'composer.dev.json');
  uncomment_line('.gitattributes', 'patches');
  uncomment_line('.gitattributes', 'package-lock.json');
  uncomment_line('.gitattributes', 'package.json');
  uncomment_line('.gitattributes', 'phpcs.xml');
  uncomment_line('.gitattributes', 'phpmd.xml');
  uncomment_line('.gitattributes', 'phpstan.neon');
  uncomment_line('.gitattributes', 'phpunit.d10.xml');
  uncomment_line('.gitattributes', 'phpunit.xml');
  uncomment_line('.gitattributes', 'rector.php');
  uncomment_line('.gitattributes', 'renovate.json');
  uncomment_line('.gitattributes', 'tests');
  remove_string_content('# Remove the lines below in your project.');
  remove_string_content('.github/FUNDING.yml export-ignore');
  remove_string_content('LICENSE             export-ignore');

  // Rename extension files.
  @rename('your_extension.info.yml', $extension_machine_name . '.info.yml');
  @rename('your_extension.install', $extension_machine_name . '.install');
  @rename('your_extension.links.menu.yml', $extension_machine_name . '.links.menu.yml');
  @rename('your_extension.module', $extension_machine_name . '.module');
  @rename('your_extension.routing.yml', $extension_machine_name . '.routing.yml');
  @rename('your_extension.services.yml', $extension_machine_name . '.services.yml');
  @rename('config/schema/your_extension.schema.yml', 'config/schema/' . $extension_machine_name . '.schema.yml');
  @rename('src/Form/YourExtensionForm.php', 'src/Form/' . $extension_machine_name_class . 'Form.php');
  @rename('src/YourExtensionService.php', 'src/' . $extension_machine_name_class . 'Service.php');
  @rename('tests/src/Unit/YourExtensionServiceUnitTest.php', 'tests/src/Unit/' . $extension_machine_name_class . 'ServiceUnitTest.php');
  @rename('tests/src/Kernel/YourExtensionServiceKernelTest.php', 'tests/src/Kernel/' . $extension_machine_name_class . 'ServiceKernelTest.php');
  @rename('tests/src/Functional/YourExtensionFunctionalTest.php', 'tests/src/Functional/' . $extension_machine_name_class . 'FunctionalTest.php');

  // Remove scaffold files.
  @unlink('LICENSE');
  remove_dir('tests/scaffold');
  foreach (glob('.github/workflows/scaffold*.yml') ?: [] as $file) {
    @unlink($file);
  }
  remove_dir('.scaffold');

  remove_tokens_with_content('META');
  remove_special_comments();

  if ($extension_type === 'theme') {
    remove_dir('tests');
    file_put_contents($extension_machine_name . '.info.yml', 'base theme: false' . PHP_EOL, FILE_APPEND);
  }
}

/**
 * Convert a string to a specific format.
 *
 * @param string $input
 *   The input string to convert.
 * @param string $type
 *   The conversion type.
 *
 * @return string
 *   The converted string.
 */
function convert_string(string $input, string $type): string {
  return match ($type) {
    'file_name', 'route_path', 'deployment_id', 'function_name', 'ui_id', 'cli_command' => strtolower(str_replace(' ', '_', $input)),
    'domain_name', 'package_namespace' => str_replace('-', '', strtolower(str_replace(' ', '_', $input))),
    'namespace', 'class_name' => implode('', array_map(ucfirst(...), array_map(strtolower(...), preg_split('/[-_ ]+/', $input, -1, PREG_SPLIT_NO_EMPTY) ?: []))),
    'package_name' => strtolower(str_replace(' ', '-', $input)),
    'log_entry', 'code_comment_title' => $input,
    default => throw new \InvalidArgumentException('Invalid conversion type: ' . $type),
  };
}

/**
 * Replace string content in all project files recursively.
 *
 * @param string $needle
 *   The string to search for.
 * @param string $replacement
 *   The replacement string.
 */
function replace_string_content(string $needle, string $replacement): void {
  foreach (get_files() as $file) {
    $content = file_get_contents($file);
    if ($content === FALSE) {
      continue;
    }
    if (!str_contains($content, $needle)) {
      continue;
    }
    file_put_contents($file, str_replace($needle, $replacement, $content));
  }
}

/**
 * Remove lines starting with the given token from all project files.
 *
 * @param string $token
 *   The token to match at the start of lines.
 */
function remove_string_content(string $token): void {
  foreach (get_files() as $file) {
    $content = file_get_contents($file);
    if ($content === FALSE) {
      continue;
    }
    if (!str_contains($content, $token)) {
      continue;
    }
    $lines = explode("\n", $content);
    $lines = array_filter($lines, static fn(string $line): bool => !str_starts_with($line, $token));
    file_put_contents($file, implode("\n", $lines));
  }
}

/**
 * Remove blocks between token markers from all project files.
 *
 * Removes all content between lines containing "#;< TOKEN" and "#;> TOKEN"
 * markers, inclusive.
 *
 * @param string $token
 *   The token name used in the markers.
 */
function remove_tokens_with_content(string $token): void {
  $start_marker = '#;< ' . $token;
  $end_marker = '#;> ' . $token;

  foreach (get_files() as $file) {
    $content = file_get_contents($file);
    if ($content === FALSE) {
      continue;
    }
    if (!str_contains($content, $end_marker)) {
      continue;
    }
    $lines = explode("\n", $content);
    $result = [];
    $inside = FALSE;
    foreach ($lines as $line) {
      if (str_contains($line, $start_marker)) {
        $inside = TRUE;
        continue;
      }
      if (str_contains($line, $end_marker)) {
        $inside = FALSE;
        continue;
      }
      if (!$inside) {
        $result[] = $line;
      }
    }
    file_put_contents($file, implode("\n", $result));
  }
}

/**
 * Uncomment a line in a file by removing the "# " prefix.
 *
 * @param string $filename
 *   The file to modify.
 * @param string $start_string
 *   The string that follows "# " at the start of the line.
 */
function uncomment_line(string $filename, string $start_string): void {
  if (!file_exists($filename)) {
    return;
  }
  $content = file_get_contents($filename);
  if ($content === FALSE) {
    return;
  }
  $prefix = '# ' . $start_string;
  $lines = explode("\n", $content);
  foreach ($lines as &$line) {
    if (str_starts_with($line, $prefix)) {
      $line = substr($line, 2);
    }
  }
  unset($line);
  file_put_contents($filename, implode("\n", $lines));
}

/**
 * Remove all lines containing special comment markers from project files.
 */
function remove_special_comments(): void {
  foreach (get_files() as $file) {
    $content = file_get_contents($file);
    if ($content === FALSE) {
      continue;
    }
    if (!str_contains($content, '#;')) {
      continue;
    }
    $lines = explode("\n", $content);
    $lines = array_filter($lines, static fn(string $line): bool => !str_contains($line, '#;'));
    file_put_contents($file, implode("\n", $lines));
  }
}

/**
 * Get all non-binary files in the project, excluding specific directories.
 *
 * @return array<string>
 *   Array of file paths.
 */
function get_files(): array {
  $excluded = ['.git', '.idea', 'vendor', 'node_modules'];
  $directory = new \RecursiveDirectoryIterator((string) getcwd(), \FilesystemIterator::SKIP_DOTS);
  $filter = new \RecursiveCallbackFilterIterator($directory, static fn(\SplFileInfo $current): bool => !($current->isDir() && in_array($current->getFilename(), $excluded, TRUE)));
  $iterator = new \RecursiveIteratorIterator($filter);

  $files = [];
  /** @var \SplFileInfo $item */
  foreach ($iterator as $item) {
    if ($item->isFile() && !is_binary_file($item->getPathname())) {
      $files[] = $item->getPathname();
    }
  }

  return $files;
}

/**
 * Check if a file is binary by looking for null bytes.
 *
 * @param string $path
 *   The file path to check.
 *
 * @return bool
 *   TRUE if the file is binary, FALSE otherwise.
 */
function is_binary_file(string $path): bool {
  $handle = fopen($path, 'rb');
  if ($handle === FALSE) {
    return TRUE;
  }
  $chunk = fread($handle, 8192);
  fclose($handle);
  if ($chunk === FALSE) {
    return TRUE;
  }

  return str_contains($chunk, "\0");
}

/**
 * Prompt the user for input with an optional default value.
 *
 * @param string $prompt
 *   The prompt message.
 * @param string $default
 *   Optional default value.
 * @param array<string>|callable|null $validator
 *   An array of allowed values, or a callable that receives the input and
 *   returns TRUE if valid.
 *
 * @return string
 *   The user's input or the default value.
 */
function ask(string $prompt, string $default = '', array|callable|null $validator = NULL): string {
  $display = $default !== '' ? color($prompt, 'green') . ' [' . color($default, 'dim') . ']: ' : color($prompt, 'green') . ': ';
  $result = '';
  while ($result === '') {
    $input = readline($display);
    if ($input === FALSE) {
      $input = '';
    }

    $result = trim($input);
    if ($result === '' && $default !== '') {
      $result = $default;
    }

    if ($result !== '' && $validator !== NULL) {
      $valid = is_callable($validator) ? $validator($result) : in_array($result, $validator, TRUE);
      if (!$valid) {
        $result = '';
      }
    }
  }

  return $result;
}

/**
 * Prompt the user for a yes/no answer.
 *
 * @param string $prompt
 *   The prompt message.
 * @param string $default
 *   The default value ('Y' or 'N').
 *
 * @return string
 *   The lowercased answer ('y' or 'n').
 */
function ask_yesno(string $prompt, string $default = 'Y'): string {
  $options = $default === 'Y' ? 'Y/n' : 'y/N';
  $result = readline(color($prompt, 'green') . ' [' . color($options, 'dim') . ']: ');
  if ($result === FALSE || trim($result) === '') {
    $result = $default;
  }

  return strtolower($result);
}

/**
 * Remove directory recursively with all files.
 *
 * @param string $directory
 *   Path to the directory to remove.
 */
function remove_dir(string $directory): void {
  if (!is_dir($directory)) {
    return;
  }

  $items = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);

  /** @var \SplFileInfo $item */
  foreach ($items as $item) {
    $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
  }

  rmdir($directory);
}

/**
 * Wrap a string with an ANSI color code.
 *
 * @param string $text
 *   The text to colorize.
 * @param string $color
 *   The color name: green, yellow, cyan, red.
 *
 * @return string
 *   The colorized string.
 */
function color(string $text, string $color): string {
  $codes = ['red' => '31', 'green' => '32', 'yellow' => '33', 'cyan' => '36', 'dim' => '2'];

  return "\033[" . ($codes[$color] ?? '0') . 'm' . $text . "\033[0m";
}

/**
 * Show a verbose message and record messages into internal buffer.
 *
 * @param string $string
 *   Message to print.
 * @param bool|float|int|string|null ...$args
 *   Arguments to sprintf() the message.
 *
 * @return array<string>
 *   Array of messages.
 */
function verbose(string $string, ...$args): array {
  $string = sprintf($string, ...$args);

  static $buffer = [];
  $buffer[] = $string;
  if (empty(getenv('SCRIPT_QUIET'))) {
    // @codeCoverageIgnoreStart
    print end($buffer);
    // @codeCoverageIgnoreEnd
  }

  return $buffer;
}

// Entrypoint.
//
// @codeCoverageIgnoreStart
ini_set('display_errors', 1);

if (PHP_SAPI !== 'cli' || !empty($_SERVER['REMOTE_ADDR'])) {
  die('This script can be only ran from the command line.');
}

// Allow to skip the script run.
if (getenv('SCRIPT_RUN_SKIP') != 1) {
  set_error_handler(function (int $severity, string $message, string $file, int $line): bool {
    if ((error_reporting() & $severity) === 0) {
      // This error code is not included in error_reporting - continue
      // execution with the normal error handler.
      return FALSE;
    }
    throw new ErrorException($message, 0, $severity, $file, $line);
  });

  try {
    $argv = is_array($_SERVER['argv'] ?? NULL) ? array_filter($_SERVER['argv'], is_string(...)) : [];
    $argc = is_scalar($_SERVER['argc'] ?? NULL) ? (int) $_SERVER['argc'] : 0;
    // The function should not provide an exit code but rather throw exceptions.
    main($argv, $argc);
  }
  catch (\ErrorException $exception) {
    if ($exception->getSeverity() <= E_USER_WARNING) {
      verbose(PHP_EOL . 'RUNTIME ERROR: ' . $exception->getMessage() . PHP_EOL);
      exit($exception->getCode() === 0 ? 1 : $exception->getCode());
    }
  }
  catch (\Exception $exception) {
    verbose(PHP_EOL . 'ERROR: ' . $exception->getMessage() . PHP_EOL);
    exit($exception->getCode() == 0 ? 1 : $exception->getCode());
  }
}
// @codeCoverageIgnoreEnd
