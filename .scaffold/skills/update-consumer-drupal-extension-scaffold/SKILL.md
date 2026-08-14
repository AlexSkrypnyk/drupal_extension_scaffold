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
  "Bash(chmod:*)",
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
  "Bash(tar:*)"
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

Also detect the **default branch** of the repository (not the current checkout):

```bash
git symbolic-ref --short refs/remotes/origin/HEAD
```

Strip the `origin/` prefix from the result. If `origin/HEAD` is not set, fall back to the branch configured in `.github/workflows/test.yml`.

## Step 2: Get the latest published scaffold version

```bash
gh release list --repo AlexSkrypnyk/drupal_extension_scaffold --exclude-drafts --limit 5
```

`--exclude-drafts` is mandatory. Draft releases are unpublished work in
progress and must never be selected, even when they carry the highest version
number.

Take the topmost (newest) release from that list and use its tag **verbatim** -
exactly as printed, including any prefix - for the branch name, the download
and the commit message. Never reformat, shorten or normalise it.

Do not ask the user to confirm the version. If the user named a version when
invoking the skill, use that version verbatim and skip the lookup.

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

3. Create and switch to a new feature branch. Use the release tag verbatim, as
   printed in Step 2 (e.g., `4.18.0`):

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

Run init.php from the project root. Pre-fill every prompt by exporting
`PROMPTY_*` environment variables before invoking the script. Set
`PROMPTY_REMOVE_SELF=true` and `PROMPTY_PROCEED=true` to auto-accept the two
yes/no confirmations:

```bash
PROMPTY_NAME="<Name>" \
PROMPTY_MACHINE_NAME="<machine_name>" \
PROMPTY_TYPE="<type>" \
PROMPTY_CI_PROVIDER="<ci_provider>" \
PROMPTY_COMMAND_WRAPPER="<command_wrapper>" \
PROMPTY_REMOVE_SELF=true \
PROMPTY_PROCEED=true \
php init.php
```

`<command_wrapper>` accepts a comma-separated list (`ahoy`, `makefile`, or
`ahoy,makefile`), or an empty string for neither.

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

## Step 8: Remove the scaffold examples

The scaffold ships a complete example extension - sample service, form, tests,
assets, config schema and shell scripts - so that the template runs standalone.
None of it belongs in a real project. Remove it on every update, without asking
the user.

1. Delete the example shell scripts. `scripts/` is not restored in Step 7, so
   everything in it came from the extraction:

```bash
rm -f scripts/assemble-example.sh scripts/provision-example.sh scripts/start-example.sh scripts/stop-example.sh
```

2. List what the extraction left behind:

```bash
git status --porcelain --untracked-files=all
```

3. Delete every **untracked** file that belongs to the example extension.
   `init.php` renames these to the project's machine name, so match them by
   shape rather than by literal name (`<machine_name>` in file names,
   `<MachineName>` in class names):

- `src/<MachineName>Service.php`
- `src/Form/<MachineName>Form.php`
- `tests/src/Functional/<MachineName>FunctionalTest.php`
- `tests/src/FunctionalJavascript/<MachineName>FunctionalJavascriptTestBase.php`
- `tests/src/FunctionalJavascript/<MachineName>SmokeFunctionalJavascriptTest.php`
- `tests/src/Kernel/<MachineName>ServiceKernelTest.php`
- `tests/src/Unit/<MachineName>ServiceUnitTest.php`
- `config/schema/<machine_name>.schema.yml`
- `css/<machine_name>.css`
- `js/<machine_name>.js` and `js/<machine_name>.test.js`
- `<machine_name>.module`, `<machine_name>.install`,
  `<machine_name>.libraries.yml`, `<machine_name>.links.menu.yml`,
  `<machine_name>.routing.yml`, `<machine_name>.services.yml`

**Delete only paths that git reports as untracked (`??`).** A file at one of
these paths that git already tracks is the project's own code restored in
Step 7 - leave it alone.

4. Remove directories left empty by the deletions (e.g. `css/`, `js/`,
   `src/Form/`). Never leave an empty directory in the tree.

5. If the project has no JavaScript or CSS of its own once the examples are
   gone, check that `package.json`, `jest.config.js` and the stylelint config
   no longer point at removed assets. Step 11 will surface any that do.

## Step 9: Review changes

Use `git diff` and `git status` to review all changes. Pay attention to:

- **Branch references**: Replace scaffold default branch (`1.x`) with the
  project's main branch in workflow files and README.
- **README.md**: Rebuild from the scaffold template (see README section below).
- **Removed files**: If `git status` shows deleted files that were old scaffold
  infrastructure (e.g., `phpmd.xml`), confirm they are intentionally removed in
  the new scaffold version.
- **New files**: Review any new files from the scaffold to ensure they are
  infrastructure, not placeholder stubs. Anything the example extension missed
  in Step 8 - a generic service class, form class or test stub - is removed
  here.

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

## Step 10: Commit

Stage all changes and commit:

```text
Updated scaffold to <version>.
```

## Step 11: Build, lint, and test

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

## Step 12: Open PR

Push the branch and open a pull request. Use the `/open-pr` skill or create
the PR manually with a summary of all changes.

## Important notes

- Never pick a draft release. Always take the newest published one, use its tag
  verbatim, and start without asking the user to confirm it.
- Always remove the scaffold's example extension and example scripts - a real
  project never ships them.
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
- Use non-interactive flags or env vars for scripts that support them (e.g. `composer --no-interaction`, `PROMPTY_*` for `init.php`)
- For git commits, use: `git commit -m "Message here."`
