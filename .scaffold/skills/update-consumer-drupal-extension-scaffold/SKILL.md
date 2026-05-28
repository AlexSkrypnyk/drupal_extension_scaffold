---
name: update-consumer-drupal-extension-scaffold
description: Update a Drupal extension project to the latest version of drupal_extension_scaffold
user-invocable: true
---

# Update Drupal Extension Scaffold

When this skill is triggered, follow the steps below to update the current
project's infrastructure to the latest version of
[drupal_extension_scaffold](https://github.com/AlexSkrypnyk/drupal_extension_scaffold).

## Step 0: Ensure required permissions

This skill requires several Bash commands to run without prompts. Before doing
anything else, read `.claude/settings.local.json` (create it if it does not exist) and
ensure the following entries are present in `permissions.allow`. Add only the
missing ones:

```json
[
  "Bash(ahoy:*)",
  "Bash(cat:*)",
  "Bash(chmod:*)"
  "Bash(composer:*)",
  "Bash(cp:*)",
  "Bash(find:*)",
  "Bash(gh:*)",
  "Bash(git:*)",
  "Bash(grep:*)",
  "Bash(ls:*)",
  "Bash(make:*)",
  "Bash(mkdir:*)",
  "Bash(mv:*)",
  "Bash(php:*)",
  "Bash(rm:*)",
  "Bash(tar:*)",
]
```

If any entries were added, tell the user what was added and ask them to restart
the session. Permissions are loaded at startup, so changes made mid-session do
not take effect. **STOP here and do not continue** - the user must restart
Claude Code and re-invoke the skill for the new permissions to apply.

If all entries are already present, proceed to Step 1.

## Step 1: Detect current project settings

Read the project to determine the init.php answers:

1. **Name**: Read from `*.info.yml` - the `name` field.
2. **Machine name**: The `*.info.yml` filename without extension.
3. **Type**: `module` or `theme` - from the `type` field in `*.info.yml`.
4. **CI provider**: `gha` if `.github/workflows/` exists, `circleci` if `.circleci/` exists.
5. **Command wrapper**: `ahoy` if `.ahoy.yml` exists, `makefile` if only `Makefile` exists, `none` otherwise.

Also detect the **main branch** from git:

```bash
git rev-parse --abbrev-ref HEAD
```

Or check the branch configured in `.github/workflows/test.yml`.

## Step 2: Get the latest scaffold version

```bash
gh release list --repo AlexSkrypnyk/drupal_extension_scaffold --limit 5
```

Pick the latest non-draft release. Confirm the version with the user before
proceeding.

## Step 3: Prepare the feature branch

Before making any changes, ensure the working tree is on the default branch and
up to date, then create a feature branch.

1. Switch to the default (main) branch detected in Step 1:

```bash
git checkout <main_branch>
```

2. Pull the latest changes:

```bash
git pull
```

3. Create and switch to a new feature branch. Use the short version without the
   `v` prefix (e.g., `4.12`):

```bash
git checkout -b feature/update-drupal-extension-scaffold-<version>
```

This step is **mandatory** - never apply scaffold changes directly to the
default branch.

## Step 4: Clean project root

Delete everything in the project root **except** `.claude/`, `.git/` and `.idea`.

**IMPORTANT:** This command MUST use a relative path (`.`) and be run from the
project root. Using an absolute path causes `-name '.'` to fail to match the
root directory, which results in the root directory itself (including `.git/`)
being deleted. Ensure the shell working directory is the project root before
running this command.

```bash
find . -maxdepth 1 ! -name '.' ! -name '.git' ! -name '.claude' ! -name '.idea' -exec rm -rf {} +
```

## Step 5: Download and extract scaffold

Download the release archive directly into the project root (not a git clone -
the release archive has scaffold-only files removed):

```bash
gh release download <version> --repo AlexSkrypnyk/drupal_extension_scaffold --archive tar.gz --output drupal_extension_scaffold.tar.gz
```

```bash
tar -xzf drupal_extension_scaffold.tar.gz --strip-components=1
```

```bash
rm drupal_extension_scaffold.tar.gz
```

## Step 6: Run init.php

Run init.php from the project root. All 5 positional args skip interactive
prompts, and `--no-interaction` auto-accepts the two yes/no confirmations
with their defaults (both Y):

```bash
php init.php "<Name>" "<machine_name>" "<type>" "<ci_provider>" "<command_wrapper>" --no-interaction
```

## Step 7: Restore project-specific files from git

The scaffold extraction overwrote project-specific files. Restore them from
the previous commit:

```bash
git checkout HEAD -- \
  src/ \
  tests/ \
  config/ \
  composer.json \
  LICENSE \
  *.module \
  *.install \
  *.info.yml \
  *.services.yml \
  *.routing.yml \
  *.links.menu.yml \
  *.permissions.yml \
  *.libraries.yml
```

Only restore paths that actually exist in the project - skip any that produce
errors.

## Step 8: Review changes

Use `git diff` and `git status` to review all changes. Pay attention to:

- **Branch references**: Replace scaffold default branch (`1.x`) with the
  project's main branch in workflow files and README.
- **README.md**: Rebuild from the scaffold template (see README section below).
- **Removed files**: If `git status` shows deleted files that were old scaffold
  infrastructure (e.g., `phpmd.xml`), confirm they are intentionally removed in
  the new scaffold version.
- **New files**: Review any new files from the scaffold to ensure they are
  infrastructure, not placeholder stubs (generic service classes, form classes,
  or test stubs should be removed).

### README.md handling

The `README.md` must follow the scaffold template structure exactly. Do NOT
simply patch path references - instead, rebuild it from the scaffold's
`README.md` template:

1. Start with the scaffold's `README.md` as the base structure.
2. Replace scaffold placeholder values with project-specific values:
   - Badge URLs (GitHub org/repo).
   - Logo URL or image.
   - Project title/description (from `*.info.yml` and existing README).
3. Insert project-specific content sections (e.g., "Use case", "How it works",
   "Installation") between the header and the "Local development" section.
4. Keep all scaffold development sections verbatim (Local development, Building
   website, Drupal versions, Coding standards, Testing, etc.).
5. Adjust command references to match the chosen command wrapper (e.g., remove
   `make` references if the project uses `ahoy` only, or vice versa).
6. Remove badges for tools not used (e.g., CircleCI badge if using GHA).
7. Fix the scaffold link at the bottom to point to the scaffold repo, not the
   project repo.

## Step 9: Commit

Stage all changes and commit:

```
Updated scaffold to <version>.
```

## Step 10: Build, lint, and test

Run the full build pipeline to verify nothing is broken:

```bash
ahoy build
```

If `ahoy` is not available, run the devtools scripts as separate commands:

```bash
.devtools/assemble
```

```bash
.devtools/start
```

```bash
.devtools/provision
```

Then run linting and tests:

```bash
ahoy lint
```

```bash
ahoy test
```

Both lint **warnings** and **errors** must be fixed - warnings are not accepted.
Same applies to tests: all warnings and failures must be resolved before
proceeding. Fix the issues and create additional commits.

### PHPUnit doc-comment deprecations

PHPUnit 11 (used by Drupal 11) reports deprecations about `@covers` and
`@group` doc-comment annotations, suggesting PHP attributes instead. **Do NOT
convert these to PHP attributes** - Drupal 10 uses PHPUnit 10 which does not
have these attribute classes, and PHPStan will fail with "Attribute class does
not exist" errors. Keep using doc-comment annotations (`@covers`, `@group`)
for cross-version compatibility. The PHPUnit deprecation warnings are
acceptable.

## Step 11: Open PR

Push the branch and open a pull request. Use the `/open-pr` skill or create
the PR manually with a summary of all changes.

## Important notes

- Always confirm the scaffold version with the user before starting.
- Never overwrite project-specific code (src/, tests/, config/, *.module, etc.).
- After copying workflow files, always verify branch references match the project.
- If the devtools scripts changed format (e.g., Bash to PHP), remove the old
  files and copy the new ones - do not try to merge them.
- Run the full test suite before opening the PR to catch regressions.
- Prefer `ahoy` or `Makefile` commands over running tools directly. For example,
  use `ahoy lint` instead of `composer lint`, `ahoy test` instead of
  `composer test`, `ahoy build` instead of running `.devtools/*` scripts
  manually. Only fall back to direct commands when no `ahoy` or `Makefile`
  equivalent exists.

## Working directory rules

- **Never `cd` into the `build/` directory** to run commands. All commands
  (`ahoy lint`, `ahoy test`, `ahoy build`, etc.) must be run from the project
  root directory.
- **Never use absolute paths** to run commands. Use relative paths or let
  `ahoy`/`make` handle path resolution.

## Command pattern rules

Commands must start with a simple keyword that matches the allowed permission
prefixes (e.g., `git`, `php`, `rm`, `cp`, `ahoy`). Avoid patterns that trigger
CLI approval prompts:

NEVER use compound or composite commands in a single Bash tool call.
Every Bash call must contain exactly ONE simple command. No exceptions.

**NEVER use:** `&&`, `||`, `;`, `|`, `<<<`, `$(...)`, heredocs.

**ALWAYS:**
- Use multiple separate Bash tool calls, one command per call
- Use `--no-interaction` flag for scripts that support it
- For git commits, use: `git commit -m "Message here."`
