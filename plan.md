# Plan: Convert `init.sh` to PHP

## Overview

Move all functionality from `init.sh` into the existing `init.php` single-file
CLI script. The `init.php` already has the scaffold (entrypoint, error handling,
`verbose()`, `remove_dir()`, `print_help()`). The task is to replace the
placeholder `main()` logic with the real init logic from `init.sh`.

## File to modify

### `init.php` — Single-file script (already exists)

Replace the placeholder `main()` and `print_help()` with full init
functionality. All functions stay in this one file. No classes, no autoloader.

#### Functions to add (ported from `init.sh`)

- `convert_string(string $input, string $type): string` — 11 conversion types
  (`file_name`, `route_path`, `deployment_id`, `domain_name`,
  `package_namespace`, `namespace`, `class_name`, `package_name`,
  `function_name`, `ui_id`, `cli_command`, `log_entry`, `code_comment_title`).
- `replace_string_content(string $needle, string $replacement): void` —
  Recursive find/replace across all project files (excluding `.git`, `.idea`,
  `vendor`, `node_modules`).
- `remove_string_content(string $token): void` — Remove lines starting with
  `$token` from all project files.
- `remove_tokens_with_content(string $token): void` — Remove blocks between
  `#;< TOKEN` and `#;> TOKEN` markers.
- `uncomment_line(string $filename, string $start_string): void` — Uncomment a
  line starting with `# <start_string>` by removing the `# ` prefix.
- `remove_special_comments(): void` — Remove all lines containing `#;`.
- `ask(string $prompt, string $default = ''): string` — Interactive prompt with
  optional default value.
- `ask_yesno(string $prompt, string $default = 'Y'): string` — Y/N prompt.
- `process_readme(string $extension_name): void` — Rename `README.dist.md` to
  `README.md` and download placeholder logo.
- `process_internal(string $name, string $machine_name, string $type): void` —
  All string replacements, file renames, `.gitattributes` uncommenting, cleanup.

#### `main()` rewrite

Port the interactive flow from `init.sh`:
1. Collect inputs (name, machine_name, type, ci_provider, command_wrapper,
   remove_self) — interactively or from CLI args.
2. Print summary.
3. Confirm proceed.
4. Validate required values.
5. Remove unwanted CI provider files.
6. Remove unwanted command wrapper files.
7. Call `process_readme()`.
8. Call `process_internal()`.
9. Optionally remove `init.php` itself.

#### `print_help()` rewrite

Update to reflect the init script usage and arguments.

#### Existing functions to keep as-is

- `verbose()` — Already present, works as needed.
- `remove_dir()` — Already present, used for `.scaffold` cleanup.

## Constraints

- PHP 8.2+ compatible.
- No external dependencies (runs before `composer install`).
- Single file — everything in `init.php`.
- Line length up to 160 characters.
- Follow existing code style in `init.php` (snake_case functions, `FALSE`/`TRUE`
  constants, `verbose()` for output).

## Files to remove

- `init.sh` — Replaced by `init.php`.

## Out of scope

- Test updates (BATS and PHPUnit tests will be addressed separately later).
