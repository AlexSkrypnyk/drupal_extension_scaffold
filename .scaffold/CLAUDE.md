# Scaffold Maintenance

Maintenance guide for the `drupal_extension_scaffold` template itself.

This file documents how to regenerate the scaffold's own artefacts (animated
README SVGs, snapshot fixtures) and how to run its self-tests. It does **not**
apply to consumer projects produced by running `init.php`.

## Layout

- `.scaffold/assets/` - Source files for animated SVG demos used in the root `README.md` (`init.svg`, `build.svg`, `lint.svg`, `test.svg`) plus the `update-assets.php` generator and a small `svg-term` Node wrapper.
- `.scaffold/tests/` - PHPUnit suite that validates the scaffold itself: the `init.php` interactive flow, the `.devtools/*` PHP helpers, and the resulting project structure. Snapshots live under `.scaffold/tests/fixtures/init/`.

## Test groups

PHPUnit tests are tagged with `#[Group('p0'..'p5')]` so CI can shard them across parallel jobs:

- `p0` - Unit tests in `src/Unit/` (no I/O bound dependencies).
- `p1` - `InitTest` (snapshot comparison of `init.php` output).
- `p2` - `AssembleTest` (Drupal codebase assembly).
- `p3` - `AhoyTest`, `AutoPortDiscoveryTest` (Ahoy command wrapper).
- `p4` - `MakeTest` (Makefile command wrapper).
- `p5` - `XdebugTest` (XDebug step-debugging toggle). This is the only group whose CI runner installs the xdebug PHP extension via `coverage: xdebug` instead of pcov - the test exercises the real extension end to end, so coverage is not collected for this group.

## Running the tests

All commands run from `.scaffold/tests/`. Install dependencies once with `composer --working-dir=.scaffold/tests install`.

| Action                        | Command                                                                                  |
|-------------------------------|------------------------------------------------------------------------------------------|
| All tests                     | `composer --working-dir=.scaffold/tests test`                                            |
| Single group                  | `composer --working-dir=.scaffold/tests test -- --group=p0`                              |
| Single test class             | `composer --working-dir=.scaffold/tests test -- --filter=InitTest`                       |
| With coverage                 | `composer --working-dir=.scaffold/tests test-coverage -- --group=p0`                     |
| Lint (phpcs, phpstan, rector) | `composer --working-dir=.scaffold/tests lint`                                            |
| Lint autofix                  | `composer --working-dir=.scaffold/tests lint-fix`                                        |

`p2`-`p5` exercise the full build pipeline and need a Drupal-friendly PHP setup; `p3` additionally needs Selenium. These are the same jobs the GitHub Actions matrix runs (`.github/workflows/scaffold-test.yml`).

## Regenerating snapshot fixtures

**HARD RULE - never edit fixtures directly.** Files under `.scaffold/tests/fixtures/init/` are generated artefacts. They must always be regenerated with the `update-snapshots` Composer script - run from inside `.scaffold/tests` (see below) - after any source change that affects `init.php` output. Hand-editing a fixture risks drift between what the generator would produce and what is checked in - subsequent regenerations would then overwrite the manual edit and the failure mode would only surface in CI.

`InitTest` runs `init.php` end-to-end and diffs the output against `fixtures/init/_baseline/` plus one fixture directory per dataset (`circleci/`, `gha_makefile/`, `theme/`, etc. - see `InitTest::dataProviderInit()`).

When source files change (workflows, `.devtools/`, `init.php`, Claude settings, etc.), the fixtures fall out of date. Regenerate them.

**HARD RULE - regenerate snapshots in this exact order. Never manipulate `TMPDIR` and never pass `--jobs`.**

1. Commit your current source changes first (as their own commit).
2. `cd` into `.scaffold/tests`.
3. Run `composer update-snapshots`.

```bash
cd .scaffold/tests
composer update-snapshots
```

Committing the source first matters because `update-snapshots` stages, commits, and amends the fixture diffs via `git`: with the source already in its own commit, the regenerated fixtures land in a separate, clean commit on top instead of mixing the two changesets.

This wraps `vendor/bin/update-snapshots` from `alexskrypnyk/snapshot`. It:

1. Runs the baseline dataset first and commits any baseline diff as its own commit.
2. Runs the remaining datasets and amends the baseline commit with each fixture diff.
3. Exits non-zero on the first run because the original tests failed against the stale snapshots - that is expected; the snapshots are now correct.

After it finishes, `git show --stat` the resulting commit to confirm it touches only the files your change should have affected, then run `composer test -- --filter=InitTest` from inside `.scaffold/tests` to confirm everything is green before pushing.

The trait that drives the diff-and-update behaviour is `SnapshotTrait` (see `tearDown()` in `InitTest`); it calls `snapshotUpdateOnFailure()` so a normal `test` run will also rewrite fixtures if you have not used the dedicated `update-snapshots` command.

## Regenerating animated SVG assets

The README demo SVGs are produced from real terminal recordings:

```bash
composer --working-dir=.scaffold/tests update-assets
```

This invokes `php .scaffold/assets/update-assets.php`, which:

1. Creates a clean workspace, copies the scaffold into it, and installs `svg-term` via `npm install --prefix .scaffold/assets`.
2. Records `init.php` and `ahoy build` sequentially using `asciinema` + `expect`.
3. Records `ahoy lint` and `ahoy test` in parallel against the assembled workspace.
4. Converts each `.cast` file to an animated SVG via `node .scaffold/assets/svg-term-render.js` and writes `init.svg`, `build.svg`, `lint.svg`, `test.svg` into `.scaffold/assets/`.

Required tools: `asciinema`, `expect`, `node`, `npm`. The script checks for these and aborts if any are missing.

Set `SCRIPT_QUIET=1` to suppress verbose progress messages. To record a single asset, pass `--record <name> --workspace <dir>`.

## CI

`.github/workflows/scaffold-test.yml` runs the suite across the `p0`-`p5` groups and validates `composer.json` (validate + normalize) plus the PHP lint step in `p0`. A second job (`scaffold-test-actions`) lints the workflow YAML with `yamllint` and `actionlint`.
