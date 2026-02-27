<?php

/**
 * @file
 * Helper functions for DevTools tooling scripts.
 *
 * This file provides reusable PHP helper functions for Vortex notification
 * and utility scripts, enabling consistent behavior across all tooling.
 *
 * ## Why We Use These Helpers
 *
 * These helper functions serve several critical purposes:
 *
 * 1. **Consistency**: Standardized output formatting (info, task, pass, fail)
 *    ensures all Vortex scripts produce uniform, recognizable messages.
 *
 * 2. **Reusability**: Common operations (files operations, command execution,
 *    etc.) are centralized to avoid code duplication.
 *
 * 3. **Testability**: All functions are designed to be mockable and testable,
 *    with comprehensive unit tests ensuring reliability.
 *
 * 4. **Maintainability**: Changes to core functionality (e.g., output
 *    formatting) only need to be made in one place.
 *
 * @phpcs:disable Drupal.NamingConventions.ValidFunctionName.InvalidName
 */

declare(strict_types=1);

namespace DrupalExtensionScaffold\DevTools;

/**
 * Get environment variable with fallback and default value.
 */
function getenv_default(mixed ...$vars): string {
  if (count($vars) < 2) {
    throw new \InvalidArgumentException('getenv_default() requires at least 2 arguments: one or more variable names and a default value');
  }

  $default = array_pop($vars);

  foreach ($vars as $var) {
    $value = is_string($var) ? getenv($var) : $default;
    if ($value !== FALSE && is_string($value) && $value !== '') {
      return $value;
    }
  }

  return is_string($default) ? $default : '';
}

/**
 * Get required environment variable with fallback support.
 */
function getenv_required(mixed ...$vars): string {
  if (count($vars) < 1) {
    throw new \InvalidArgumentException('getenv_required() requires at least 1 argument');
  }

  $vars[] = '';

  $value = getenv_default(...$vars);

  if ($value !== '') {
    return $value;
  }

  FAIL('Missing required value for %s', implode(', ', array_filter($vars)));

  // @codeCoverageIgnoreStart
  return '';
  // @codeCoverageIgnoreEnd
}

/**
 * Output a note message.
 */
function NOTE(string $format, string|int|float ...$args): void {
  echo sprintf('       %s%s', sprintf($format, ...$args), PHP_EOL);
}

/**
 * Output a task message.
 */
function TASK(string $format, string|int|float ...$args): void {
  echo term_supports_color() ?
    "\033[34m[TASK] " . sprintf($format, ...$args) . "\033[0m\n" :
    sprintf('[TASK] %s%s', sprintf($format, ...$args), PHP_EOL);
}

/**
 * Output an info message.
 */
function INFO(string $format, string|int|float ...$args): void {
  echo term_supports_color() ?
    "\033[36m[INFO] " . sprintf($format, ...$args) . "\033[0m\n" :
    sprintf('[INFO] %s%s', sprintf($format, ...$args), PHP_EOL);
}

/**
 * Output a success message.
 */
function PASS(string $format, string|int|float ...$args): void {
  echo term_supports_color() ?
    "\033[32m[ OK ] " . sprintf($format, ...$args) . "\033[0m\n" :
    sprintf('[ OK ] %s%s', sprintf($format, ...$args), PHP_EOL);
}

/**
 * Output a failure message.
 */
function FAIL(string $format, string|int|float ...$args): void {
  FAIL_NO_EXIT($format, ...$args);
  quit(1);
}

/**
 * Output a failure message and do not exit.
 */
function FAIL_NO_EXIT(string $format, string|int|float ...$args): void {
  echo term_supports_color() ?
    "\033[31m[FAIL] " . sprintf($format, ...$args) . "\033[0m\n" :
    sprintf('[FAIL] %s%s', sprintf($format, ...$args), PHP_EOL);
}

/**
 * Check if terminal supports colors.
 */
function term_supports_color(): bool {
  return getenv('TERM') === 'dumb' || getenv('TERM') === FALSE ? FALSE : function_exists('posix_isatty') && @posix_isatty(STDOUT);
}

/**
 * Get the path to a command, or FALSE if the command does not exist.
 */
function command_path(string $command): string|false {
  if (!preg_match('/^[A-Za-z0-9_\-]+(?: [A-Za-z0-9_\-]+)*$/', $command)) {
    return FALSE;
  }
  exec(sprintf('command -v %s 2>/dev/null', $command), $output, $code);
  return $code === 0 && !empty($output[0]) ? trim($output[0]) : FALSE;
}

/**
 * Require a command to be available, or fail.
 */
function command_must_exist(string $command): void {
  if (!command_path($command)) {
    FAIL("Command '%s' is not available", $command);
  }
}

/**
 * Run a command via passthru, failing if exit code is non-zero.
 */
function passthru_or_fail(string $cmd, string $format = '', string|int|float ...$args): void {
  passthru($cmd, $exit_code);
  if ($exit_code !== 0) {
    if ($format !== '') {
      FAIL($format, ...$args);
    }
    quit($exit_code);
  }
}

/**
 * Run a drush command.
 *
 * @param string $command
 *   The drush command, optionally with sprintf-style placeholders.
 * @param string|string[]|null $args
 *   Arguments to substitute into the command. Each argument is escaped
 *   with escapeshellarg() before substitution.
 * @param int|null &$exit_code
 *   If provided, the exit code is stored here and failures do not cause
 *   the script to exit. If not provided, non-zero exit codes call fail().
 *
 * @param-out int $exit_code
 */
function drush(string $command, mixed $args = NULL, ?int &$exit_code = NULL): string {
  if (is_string($args)) {
    $args = [$args];
  }

  if (is_array($args) && $args !== []) {
    $command = sprintf($command, ...array_map(escapeshellarg(...), $args));
  }

  $exit_code_provided = $exit_code !== NULL;
  $exit_code = 0;

  $command = 'build/vendor/bin/drush -r ' . escapeshellarg(getcwd() . '/build/web') . ' -y ' . $command;

  ob_start();
  passthru($command, $exit_code);
  $output = ob_get_clean();

  if (!$exit_code_provided && $exit_code !== 0) {
    FAIL('Drush command failed: %s', $command);
  }

  return $output ?: '';
}

/**
 * Get extension information from the .info.yml file.
 *
 * @return array{name: string, type: string}
 *   Array with 'name' (machine name) and 'type' ('module' or 'theme').
 */
function extension_info(): array {
  $info_files = glob('*.info.yml');
  if ($info_files === FALSE || $info_files === []) {
    FAIL('No .info.yml file found.');

    return ['name' => '', 'type' => ''];
  }
  $name = basename($info_files[0], '.info.yml');
  $type = str_contains((string) file_get_contents($info_files[0]), 'type: theme') ? 'theme' : 'module';

  return ['name' => $name, 'type' => $type];
}

/**
 * Check if debug mode is enabled.
 */
function is_debug(): bool {
  return getenv('DEBUG') === '1';
}

/**
 * Replace content in a file using a regular expression.
 *
 * @return string
 *   The file content after replacement.
 */
function replace_in_file(string $file, string $pattern, string $replacement): string {
  $content = file_get_contents($file);
  if ($content === FALSE) {
    FAIL('Unable to read file %s.', $file);

    return '';
  }

  $replaced = preg_replace($pattern, $replacement, $content);
  if ($replaced === NULL) {
    FAIL('Regex replacement failed in file %s with pattern %s.', $file, $pattern);

    return $content;
  }

  file_put_contents($file, $replaced);

  return $replaced;
}

/**
 * Recursively remove a directory.
 */
function remove_dir(string $directory): void {
  if (!is_dir($directory)) {
    return;
  }
  $items = new \RecursiveIteratorIterator(
    new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::UNIX_PATHS),
    \RecursiveIteratorIterator::CHILD_FIRST
  );
  /** @var \SplFileInfo $item */
  foreach ($items as $item) {
    $path = $item->getPathname();
    if (is_link($path)) {
      @unlink($path);
    }
    elseif ($item->isDir()) {
      @rmdir($path);
    }
    else {
      @unlink($path);
    }
  }
  @rmdir($directory);
}

/**
 * Recursively copy a directory.
 */
function copy_dir(string $src, string $dst): void {
  if (!is_dir($dst)) {
    mkdir($dst, 0755, TRUE);
  }
  $iterator = new \RecursiveIteratorIterator(
    new \RecursiveDirectoryIterator($src, \RecursiveDirectoryIterator::SKIP_DOTS),
    \RecursiveIteratorIterator::SELF_FIRST
  );
  /** @var \RecursiveDirectoryIterator $item */
  foreach ($iterator as $item) {
    $target = $dst . DIRECTORY_SEPARATOR . $iterator->getSubPathname();
    if ($item->isDir()) {
      if (!is_dir($target)) {
        mkdir($target, 0755, TRUE);
      }
    }
    else {
      copy($item->getPathname(), $target);
    }
  }
}

/**
 * Recursively chmod a directory.
 */
function chmod_recursive(string $path, int $mode): void {
  if (!is_dir($path)) {
    return;
  }
  $iterator = new \RecursiveIteratorIterator(
    new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::UNIX_PATHS),
    \RecursiveIteratorIterator::SELF_FIRST
  );
  /** @var \SplFileInfo $item */
  foreach ($iterator as $item) {
    if (!is_link($item->getPathname())) {
      @chmod($item->getPathname(), $mode);
    }
  }
  @chmod($path, $mode);
}

// Never run the real quit() function during tests. This also avoids bleeding
// into global namespace when running multiple tests that share the same
// test process.
// Note that this replicates the behaviour of global built-in functions
// like passthru() and exec() which are *not defined in this namespace*. We only
// defined quit() in a namespace because mocking of global functions can only
// be done if they are defined in a namespace.
// @codeCoverageIgnoreStart
if (!function_exists('DrupalExtensionScaffold\DevTools\quit') && !class_exists('PHPUnit\\Framework\\TestCase')) {

  /**
   * Exit script with given code.
   *
   * Wrapper around exit() to allow mocking in tests since exit() cannot be
   * directly mocked despite being a function in PHP 8.4+.
   *
   * @param int $code
   *   Exit code (0 for success, non-zero for error).
   */
  function quit(int $code = 0): void {
    exit($code);
  }

}
// @codeCoverageIgnoreEnd
