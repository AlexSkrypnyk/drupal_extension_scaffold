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
 * Read variables from a dotenv-style file into an associative array.
 *
 * Parses lines of the form KEY=VALUE. Lines that are blank or start with
 * '#' (after optional whitespace) are ignored. Surrounding single or double
 * quotes around the value are stripped. Keys and values are trimmed.
 *
 * @param string $file
 *   Path to the dotenv file.
 *
 * @return array<string, string>
 *   Associative array of key-value pairs. Empty if the file does not exist
 *   or contains no parseable lines.
 */
function dotenv_read(string $file = '.env'): array {
  if (!file_exists($file)) {
    return [];
  }

  $contents = file_get_contents($file);
  if ($contents === FALSE) {
    return [];
  }

  $vars = [];
  foreach (preg_split('/\r\n|\r|\n/', $contents) ?: [] as $line) {
    $trimmed = trim($line);
    if ($trimmed === '') {
      continue;
    }
    if (str_starts_with($trimmed, '#')) {
      continue;
    }

    if (!str_contains($trimmed, '=')) {
      continue;
    }

    [$key, $value] = explode('=', $trimmed, 2);
    $key = trim($key);
    $value = trim($value);

    if ($key === '') {
      continue;
    }

    if (strlen($value) >= 2) {
      $first = $value[0];
      $last = $value[strlen($value) - 1];
      if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
        $value = substr($value, 1, -1);
      }
    }

    $vars[$key] = $value;
  }

  return $vars;
}

/**
 * Set or update a variable in a dotenv-style file.
 *
 * If the key already exists in the file, its line is rewritten in place,
 * preserving the order of other lines and any comments or blank lines.
 * If the key does not exist, the assignment is appended to the end of the
 * file. The file is created with the single assignment if it does not exist.
 *
 * @param string $key
 *   Variable name to write.
 * @param string $value
 *   Variable value to write. Not quoted.
 * @param string $file
 *   Path to the dotenv file.
 */
function dotenv_write_var(string $key, string $value, string $file = '.env'): void {
  $assignment = sprintf('%s=%s', $key, $value);

  if (!file_exists($file)) {
    if (file_put_contents($file, $assignment . PHP_EOL) === FALSE) {
      FAIL('Unable to write %s', $file);
    }

    return;
  }

  $contents = file_get_contents($file);
  if ($contents === FALSE) {
    FAIL('Unable to read %s', $file);

    // @codeCoverageIgnoreStart
    return;
    // @codeCoverageIgnoreEnd
  }

  $lines = preg_split('/\r\n|\r|\n/', $contents) ?: [];
  $trailing_newline = $contents !== '' && (str_ends_with($contents, "\n") || str_ends_with($contents, "\r"));
  if ($trailing_newline && end($lines) === '') {
    array_pop($lines);
  }

  // Replace the LAST matching assignment so dotenv_read()'s
  // last-assignment-wins semantics agree with what is written.
  $replace_index = NULL;
  foreach ($lines as $i => $line) {
    $trimmed = trim($line);
    if ($trimmed === '') {
      continue;
    }
    if (str_starts_with($trimmed, '#')) {
      continue;
    }
    if (!str_contains($trimmed, '=')) {
      continue;
    }

    [$existing_key] = explode('=', $trimmed, 2);
    if (trim($existing_key) === $key) {
      $replace_index = $i;
    }
  }

  if ($replace_index === NULL) {
    $lines[] = $assignment;
  }
  else {
    $lines[$replace_index] = $assignment;
  }

  if (file_put_contents($file, implode(PHP_EOL, $lines) . PHP_EOL) === FALSE) {
    FAIL('Unable to write %s', $file);
  }
}

/**
 * Resolve an environment value from env, then dotenv, then default.
 *
 * Mirrors the precedence chain used by the start, stop, provision, and
 * info scripts: shell environment first, then a matching key in the
 * dotenv file, then the supplied default. Returns the resolved value
 * together with a source label identifying which leg of the chain
 * produced it.
 *
 * @param string $name
 *   Variable name to resolve.
 * @param string $default
 *   Value to return when neither env nor the dotenv file provides a
 *   non-empty value. May be an empty string when callers want to
 *   detect that case and apply their own fallback (for example,
 *   start's auto-discovery branch).
 * @param string $dotenv_file
 *   Path to the dotenv file to consult.
 *
 * @return array{0: string, 1: string}
 *   Tuple of [value, source] where source is 'env' when the value came
 *   from the shell environment, the dotenv file path when the value
 *   came from that file, or 'default' when neither produced a value.
 */
function resolve_env_value(string $name, string $default, string $dotenv_file = '.env'): array {
  $env = getenv($name);
  if ($env !== FALSE && $env !== '') {
    return [$env, 'env'];
  }

  $dotenv = dotenv_read($dotenv_file);
  if (isset($dotenv[$name]) && $dotenv[$name] !== '') {
    return [$dotenv[$name], $dotenv_file];
  }

  return [$default, 'default'];
}

/**
 * Resolve the webserver host and port with source tracking.
 *
 * Wraps resolve_env_value() for both 'WEBSERVER_HOST' and
 * 'WEBSERVER_PORT'. Optionally auto-discovers a free port when neither
 * env nor the dotenv file provides one, persisting the discovered port
 * back to the dotenv file. Optionally validates that the resolved port
 * is a valid TCP port number.
 *
 * @param bool $auto_discover
 *   When TRUE and the port resolves from neither env nor dotenv,
 *   discover a free port via find_free_port() and persist it via
 *   dotenv_write_var(). The reported port source then becomes the
 *   dotenv file path because that is where the value now lives.
 * @param bool $validate_port
 *   When TRUE, call validate_port_or_fail() on the resolved port and
 *   abort if it is not a valid TCP port. The info script disables
 *   this so a malformed value can be surfaced in the output rather
 *   than crashing the read-only summary.
 * @param string $dotenv_file
 *   Path to the dotenv file to read and (optionally) write.
 *
 * @return array{host: string, host_source: string, port: string, port_source: string}
 *   The resolved host and port together with the source label for
 *   each: 'env' when the value came from the shell environment, the
 *   dotenv file path when it came from that file, or 'default' when
 *   the supplied default was used.
 */
function resolve_webserver(bool $auto_discover = FALSE, bool $validate_port = TRUE, string $dotenv_file = '.env'): array {
  [$host, $host_source] = resolve_env_value('WEBSERVER_HOST', 'localhost', $dotenv_file);

  $default_port = $auto_discover ? '' : '8000';
  [$port, $port_source] = resolve_env_value('WEBSERVER_PORT', $default_port, $dotenv_file);

  if ($auto_discover && $port_source === 'default') {
    $port = (string) find_free_port();
    dotenv_write_var('WEBSERVER_PORT', $port, $dotenv_file);
    $port_source = $dotenv_file;
  }

  if ($validate_port) {
    validate_port_or_fail($port, 'WEBSERVER_PORT');
  }

  return [
    'host' => $host,
    'host_source' => $host_source,
    'port' => $port,
    'port_source' => $port_source,
  ];
}

/**
 * Detect the XDebug state of the dev server listening on the given port.
 *
 * Inspects the running PHP process's command line for the
 * 'xdebug.mode=debug' flag. Returns 'enabled' or 'disabled' when a
 * server is listening, and '-' when no server is bound to the port.
 */
function xdebug_state(string $port): string {
  $cmd = sprintf('ps -o command= -p "$(lsof -ti:%s 2>/dev/null | head -1)" 2>/dev/null', escapeshellarg($port));
  $out = trim((string) @shell_exec($cmd));
  if ($out === '') {
    return '-';
  }

  return str_contains($out, 'xdebug.mode=debug') ? 'enabled' : 'disabled';
}

/**
 * Validate that a value is a TCP port in the range 1-65535.
 *
 * Calls FAIL() with a descriptive message if validation fails.
 *
 * @param string $value
 *   The value to validate.
 * @param string $source
 *   Source name used in the error message (e.g. 'WEBSERVER_PORT').
 */
function validate_port_or_fail(string $value, string $source): void {
  if (!ctype_digit($value) || (int) $value < 1 || (int) $value > 65535) {
    FAIL('Invalid %s "%s". Expected integer in range 1-65535.', $source, $value);
  }
}

/**
 * Find a free TCP port by scanning a range of ports.
 *
 * Each port is probed by attempting a client connect to localhost. A
 * connection that succeeds means some server is already listening on
 * that port (on either IPv4 or IPv6, whichever the resolver picks).
 * A connection that is refused means the port is free. This works on
 * environments without IPv6 (e.g. Docker-based CI) where a server-side
 * bind probe to [::1] would always fail. Calls FAIL() if no free port
 * is found within $max_attempts.
 *
 * @param int $start
 *   Port number to start scanning from.
 * @param int $max_attempts
 *   Maximum number of ports to try.
 *
 * @return int
 *   The first free port found.
 */
function find_free_port(int $start = 8000, int $max_attempts = 100): int {
  if ($start < 1 || $start > 65535) {
    FAIL('Start port must be between 1 and 65535, got %d', $start);
  }
  if ($max_attempts < 1) {
    FAIL('Max attempts must be a positive integer, got %d', $max_attempts);
  }

  for ($port = $start; $port < $start + $max_attempts; $port++) {
    $conn = @stream_socket_client(sprintf('tcp://localhost:%d', $port), $errno, $errstr, 0.2);
    if ($conn === FALSE) {
      return $port;
    }
    fclose($conn);
  }

  FAIL('Unable to find a free port in range %d-%d', $start, $start + $max_attempts - 1);

  // @codeCoverageIgnoreStart
  return $start;
  // @codeCoverageIgnoreEnd
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

  FAIL('Missing required value for %s', implode(', ', array_filter(array_map(static fn(mixed $v): string => is_string($v) ? $v : '', $vars))));

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
 * Run custom shell scripts from a directory matching a filename prefix.
 *
 * Scripts are matched as "<dir>/<prefix>*.sh", sorted lexicographically,
 * and executed in order. Each script runs with the current working
 * directory as the parent process's CWD and inherits the parent's
 * environment. A non-zero exit from any script aborts the parent.
 *
 * The directory and any matching scripts are optional: a missing
 * directory or an empty match set returns silently. This is the
 * post-assemble / post-provision extension point.
 *
 * @param string $dir
 *   Directory to scan, relative to the current working directory.
 * @param string $prefix
 *   Filename prefix to match (e.g. "assemble-" or "provision-").
 */
function run_custom_scripts(string $dir, string $prefix): void {
  if (!is_dir($dir)) {
    return;
  }

  $files = glob($dir . '/' . $prefix . '*.sh') ?: [];
  if ($files === []) {
    return;
  }

  sort($files);

  foreach ($files as $file) {
    if (!is_file($file)) {
      continue;
    }
    TASK("Running custom script '%s'.", $file);
    passthru_or_fail(escapeshellarg($file), "Custom script '%s' failed.", $file);
    PASS("Completed custom script '%s'.", $file);
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

    // @codeCoverageIgnoreStart
    return ['name' => '', 'type' => ''];
    // @codeCoverageIgnoreEnd
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

    // @codeCoverageIgnoreStart
    return '';
    // @codeCoverageIgnoreEnd
  }

  $replaced = preg_replace($pattern, $replacement, $content);
  if ($replaced === NULL) {
    FAIL('Regex replacement failed in file %s with pattern %s.', $file, $pattern);

    // @codeCoverageIgnoreStart
    return $content;
    // @codeCoverageIgnoreEnd
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
