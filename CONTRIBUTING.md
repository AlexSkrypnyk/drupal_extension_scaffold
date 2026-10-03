# Contributing

Thank you for your interest in improving Eddy. This guide covers working on the scaffold template itself.

When someone runs `php init.php` to create a project from this template, this file is replaced by the generated project's own `CONTRIBUTING.md` (produced from `CONTRIBUTING.dist.md`). Keep scaffold-specific notes here and consumer-facing notes in `CONTRIBUTING.dist.md`.

## What lives where

The repository is a working Drupal extension - the demo extension in the project root - plus the tooling that turns it into a reusable template:

- `init.php` - the interactive script that renames, rewrites and prunes the template files for a new project.
- `.devtools/` - build and provisioning scripts shared with generated projects and used by CI.
- `.eddy/` - everything used to develop and test the scaffold itself. It is removed from generated projects.

## Building and testing the demo extension

The scaffold builds and tests itself exactly like a generated project, so the commands documented in [`README.md`](README.md) apply here too: `make build` or `ahoy build` to assemble the site, `make lint` or `ahoy lint` to check coding standards, and `make test` or `ahoy test` to run the extension tests.

## The `.eddy` directory

- `.eddy/assets/` - source files and the generator for the animated demos embedded in `README.md`.
- `.eddy/tests/` - the PHPUnit suite that validates the scaffold: the `init.php` flow, the `.devtools` helpers, and the resulting project structure. Snapshot fixtures live under `.eddy/tests/fixtures/init/`.
- `.eddy/skills/` - the `update-consumer-eddy` skill that generated projects fetch to update themselves.

## Running the scaffold self-tests

Run these from the repository root. Install the dependencies once:

```bash
composer --working-dir=.eddy/tests install
```

| Action           | Command                                                        |
|------------------|----------------------------------------------------------------|
| All tests        | `composer --working-dir=.eddy/tests test`                      |
| A single group   | `composer --working-dir=.eddy/tests test -- --group=p0`        |
| A single class   | `composer --working-dir=.eddy/tests test -- --filter=InitTest` |
| Coding standards | `composer --working-dir=.eddy/tests lint`                      |

Tests are tagged `p0` to `p5` so CI can run them as parallel jobs. `p0` is the in-process unit suite, `p1` is the `init.php` snapshot test, and `p2` to `p5` exercise the full build pipeline and need a Drupal-friendly PHP setup. `p3` and `p4` also need a WebDriver backend: a Selenium container or a local Chrome driven by chromedriver.

## Regenerating snapshot fixtures

Files under `.eddy/tests/fixtures/init/` are generated - never edit them by hand. After any change that affects `init.php` output, regenerate them:

1. Commit your source changes first, as their own commit.
2. From `.eddy/tests`, run the update command:

```bash
cd .eddy/tests
composer update-snapshots
```

It commits the regenerated baseline on its own, then amends it with each dataset fixture. Review the result with `git show --stat` and confirm `composer test -- --filter=InitTest` passes before pushing.

## Continuous integration

`.github/workflows/scaffold-test.yml` runs the suite across the `p0` to `p5` groups. See [`.eddy/CLAUDE.md`](.eddy/CLAUDE.md) for the full maintenance reference, including how to regenerate the animated demo assets.
