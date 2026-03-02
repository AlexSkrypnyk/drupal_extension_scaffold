#!/usr/bin/env php
<?php

/**
 * @file
 * Generate animated SVG assets from asciinema recordings.
 *
 * Records terminal sessions for init, build, lint, and test commands,
 * then converts the recordings to animated SVGs for use in README.md.
 *
 * Dependencies: asciinema, expect, node, npm
 *
 * Environment variables:
 * - SCRIPT_QUIET: Set to '1' to suppress verbose messages.
 *
 * Usage:
 * @code
 * php .scaffold/assets/update-assets.php
 * @endcode
 */

declare(strict_types=1);

// Terminal dimensions for recordings.
define('TERMINAL_COLS', 80);
define('TERMINAL_ROWS', 24);

// Delay before pressing enter in expect scripts (seconds).
define('PROMPT_DELAY', 1);

// Maximum idle time in recordings (seconds).
define('MAX_IDLE_TIME', 3);

/**
 * Main functionality.
 */
function main(): void {
  $script_dir = dirname(__FILE__);
  $project_dir = dirname($script_dir, 2);
  $assets_dir = $script_dir;

  info('Drupal Extension Scaffold — Asset Generator');
  info('============================================');
  info('');

  checkDependencies();

  $workspace_dir = createWorkspace($project_dir);

  info('Workspace: ' . $workspace_dir);
  info('');

  try {
    installNodeDependencies($assets_dir);

    $cast_files = [];

    // Record init.
    info('--- Recording: init ---');
    $cast_files['init'] = recordInit($workspace_dir);
    info('');

    // Record build.
    info('--- Recording: build ---');
    $cast_files['build'] = recordCommand($workspace_dir, 'build', 'ahoy build');
    info('');

    // Record lint.
    info('--- Recording: lint ---');
    $cast_files['lint'] = recordCommand($workspace_dir, 'lint', 'ahoy lint');
    info('');

    // Record test.
    info('--- Recording: test ---');
    $cast_files['test'] = recordCommand($workspace_dir, 'test', 'ahoy test');
    info('');

    // Convert casts to SVGs and install.
    info('--- Converting to SVG ---');
    foreach ($cast_files as $name => $cast_file) {
      $svg_file = $assets_dir . '/' . $name . '.svg';
      convertToSvg($cast_file, $svg_file, $assets_dir);
      info('Created: ' . $svg_file);
    }

    info('');
    info('Done. SVG assets updated in ' . $assets_dir);
  }
  finally {
    info('');
    info('Cleaning up workspace: ' . $workspace_dir);
    removeDir($workspace_dir);
  }
}

/**
 * Check that all required dependencies are installed.
 */
function checkDependencies(): void {
  $deps = ['asciinema', 'expect', 'node', 'npm'];
  $missing = [];

  foreach ($deps as $dep) {
    if (empty(shell_exec('which ' . escapeshellarg($dep) . ' 2>/dev/null'))) {
      $missing[] = $dep;
    }
  }

  if (!empty($missing)) {
    throw new \RuntimeException('Missing required dependencies: ' . implode(', ', $missing));
  }

  info('All dependencies found.');
}

/**
 * Install Node.js dependencies for svg-term rendering.
 *
 * @param string $assets_dir
 *   Path to the assets directory containing svg-term-render.js.
 */
function installNodeDependencies(string $assets_dir): void {
  info('Installing svg-term Node.js dependency...');

  $node_modules = $assets_dir . '/node_modules';
  if (is_dir($node_modules . '/svg-term')) {
    info('svg-term already installed.');

    return;
  }

  $cmd = sprintf('npm install --prefix %s svg-term@1.3.1 2>&1', escapeshellarg($assets_dir));
  $output = shell_exec($cmd);
  if (!is_dir($node_modules . '/svg-term')) {
    throw new \RuntimeException('Failed to install svg-term: ' . ($output ?? 'unknown error'));
  }

  info('svg-term installed.');
}

/**
 * Create a temporary workspace by exporting the current git tree.
 *
 * @param string $project_dir
 *   Path to the project root.
 *
 * @return string
 *   Path to the temporary workspace directory.
 */
function createWorkspace(string $project_dir): string {
  $workspace_dir = sys_get_temp_dir() . '/des-assets-' . bin2hex(random_bytes(6));
  mkdir($workspace_dir, 0755, TRUE);

  info('Exporting current branch to workspace...');

  $cmd = sprintf(
    'cd %s && git archive HEAD | tar -x -C %s 2>&1',
    escapeshellarg($project_dir),
    escapeshellarg($workspace_dir)
  );

  $output = shell_exec($cmd);
  if ($output === NULL && !file_exists($workspace_dir . '/init.php')) {
    throw new \RuntimeException('Failed to export git archive: ' . ($output ?? 'unknown error'));
  }

  return $workspace_dir;
}

/**
 * Record the init.php session using expect for automation.
 *
 * @param string $workspace_dir
 *   Path to the workspace directory.
 *
 * @return string
 *   Path to the generated cast file.
 */
function recordInit(string $workspace_dir): string {
  $cast_file = $workspace_dir . '/init.cast';
  $expect_script = $workspace_dir . '/init_automation.exp';

  createInitExpectScript($expect_script, $workspace_dir);

  $cmd = sprintf(
    'asciinema rec --command=%s --window-size=%dx%d --idle-time-limit=%d --overwrite %s 2>&1',
    escapeshellarg($expect_script),
    TERMINAL_COLS,
    TERMINAL_ROWS,
    MAX_IDLE_TIME,
    escapeshellarg($cast_file)
  );

  info('Running: asciinema rec (init)');
  $output = shell_exec($cmd);
  info($output ?? '');

  if (!file_exists($cast_file)) {
    throw new \RuntimeException('Failed to record init session.');
  }

  postProcessCast($cast_file, $workspace_dir);
  info('Recorded: ' . $cast_file);

  return $cast_file;
}

/**
 * Create an expect script to automate init.php prompts.
 *
 * @param string $script_path
 *   Path to write the expect script.
 * @param string $workspace_dir
 *   Path to the workspace directory.
 */
function createInitExpectScript(string $script_path, string $workspace_dir): void {
  $delay = PROMPT_DELAY;
  $content = <<<EXPECT
#!/usr/bin/env expect

set timeout 60
log_user 1

proc safe_send {s} {
    if {[exp_pid] > 0} {
        send -- \$s
    }
}

proc wait_and_enter {} {
    sleep {$delay}
    safe_send "\\r"
}

proc type_text {text} {
    set send_human {.1 .3 1 .05 2 .1 .2 0 .4 0 .6 0 .8 0 1}
    send -h \$text
}

cd {$workspace_dir}

spawn php init.php

# Name.
expect "Name" {
    sleep {$delay}
    type_text "Your Extension"
    wait_and_enter
}

# Machine name (accept default).
expect "Machine name" {
    wait_and_enter
}

# Type.
expect "Type" {
    sleep {$delay}
    type_text "module"
    wait_and_enter
}

# CI Provider.
expect "CI Provider" {
    sleep {$delay}
    type_text "gha"
    wait_and_enter
}

# Command wrapper.
expect "Command wrapper" {
    sleep {$delay}
    type_text "ahoy"
    wait_and_enter
}

# Remove this script.
expect "Remove this script" {
    sleep {$delay}
    type_text "y"
    wait_and_enter
}

# Proceed.
expect "Proceed" {
    sleep {$delay}
    type_text "y"
    wait_and_enter
}

expect eof
EXPECT;

  file_put_contents($script_path, $content);
  chmod($script_path, 0755);
}

/**
 * Record a command session in the workspace.
 *
 * @param string $workspace_dir
 *   Path to the workspace directory.
 * @param string $name
 *   Name for the recording (used in filenames).
 * @param string $command
 *   The command to record.
 *
 * @return string
 *   Path to the generated cast file.
 */
function recordCommand(string $workspace_dir, string $name, string $command): string {
  $cast_file = $workspace_dir . '/' . $name . '.cast';

  $wrapped_command = sprintf('cd %s && %s', escapeshellarg($workspace_dir), $command);

  $cmd = sprintf(
    'asciinema rec --command=%s --window-size=%dx%d --idle-time-limit=%d --overwrite %s 2>&1',
    escapeshellarg($wrapped_command),
    TERMINAL_COLS,
    TERMINAL_ROWS,
    MAX_IDLE_TIME,
    escapeshellarg($cast_file)
  );

  info('Running: asciinema rec (' . $name . ')');
  $output = shell_exec($cmd);
  info($output ?? '');

  if (!file_exists($cast_file)) {
    throw new \RuntimeException('Failed to record ' . $name . ' session.');
  }

  postProcessCast($cast_file, $workspace_dir);
  info('Recorded: ' . $cast_file);

  return $cast_file;
}

/**
 * Post-process a cast file.
 *
 * Removes the spawn command line and sanitizes paths.
 *
 * @param string $cast_file
 *   Path to the cast file.
 * @param string $workspace_dir
 *   Path to the workspace directory (to sanitize in output).
 */
function postProcessCast(string $cast_file, string $workspace_dir): void {
  $content = file_get_contents($cast_file);
  if ($content === FALSE) {
    return;
  }

  // Remove the spawn command line from the recording.
  // In asciicast format, the first line is the header and subsequent lines
  // are events. The spawn command (e.g., "spawn php init.php") appears as
  // one of the first events and should be stripped.
  $lines = explode("\n", $content);
  $filtered = [$lines[0]];
  for ($i = 1; $i < count($lines); $i++) {
    if (str_contains($lines[$i], 'spawn ')) {
      continue;
    }
    $filtered[] = $lines[$i];
  }
  $content = implode("\n", $filtered);

  // Sanitize workspace paths.
  $content = str_replace($workspace_dir, '/home/user/project', $content);

  // Sanitize home directory paths.
  $home = getenv('HOME');
  if ($home !== FALSE && $home !== '') {
    $content = str_replace($home, '/home/user', $content);
  }

  file_put_contents($cast_file, $content);
}

/**
 * Convert a cast file to an animated SVG.
 *
 * @param string $cast_file
 *   Path to the input cast file.
 * @param string $svg_file
 *   Path to the output SVG file.
 * @param string $assets_dir
 *   Path to the assets directory containing svg-term-render.js.
 */
function convertToSvg(string $cast_file, string $svg_file, string $assets_dir): void {
  $renderer = $assets_dir . '/svg-term-render.js';

  $cmd = sprintf(
    'node %s %s %s --line-height 1.1 2>&1',
    escapeshellarg($renderer),
    escapeshellarg($cast_file),
    escapeshellarg($svg_file)
  );

  $output = shell_exec($cmd);
  info($output ?? '');

  if (!file_exists($svg_file) || filesize($svg_file) === 0) {
    throw new \RuntimeException('Failed to convert cast to SVG: ' . $cast_file);
  }
}

/**
 * Remove a directory recursively.
 *
 * @param string $directory
 *   Path to the directory to remove.
 */
function removeDir(string $directory): void {
  if (!is_dir($directory)) {
    return;
  }

  // Use rm -rf to handle symlinks and other edge cases from the build process.
  $cmd = sprintf('rm -rf %s 2>&1', escapeshellarg($directory));
  shell_exec($cmd);
}

/**
 * Print an informational message.
 *
 * @param string $message
 *   The message to print.
 */
function info(string $message): void {
  if (getenv('SCRIPT_QUIET') === '1') {
    return;
  }
  print $message . PHP_EOL;
}

// Entrypoint.
ini_set('display_errors', '1');

if (PHP_SAPI !== 'cli' || !empty($_SERVER['REMOTE_ADDR'])) {
  die('This script can be only ran from the command line.');
}

set_error_handler(function (int $severity, string $message, string $file, int $line): bool {
  if ((error_reporting() & $severity) === 0) {
    return FALSE;
  }
  throw new \ErrorException($message, 0, $severity, $file, $line);
});

try {
  main();
}
catch (\Exception $exception) {
  info('');
  info('ERROR: ' . $exception->getMessage());
  exit(1);
}
