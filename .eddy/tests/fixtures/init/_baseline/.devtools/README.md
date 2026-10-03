This directory contains scripts used for development. These can be used locally and in the CI environment.

| Script        | Purpose                                                                                                |
|---------------|--------------------------------------------------------------------------------------------------------|
| `assemble`    | Assemble a Drupal codebase in `build/`, install dependencies, and symlink the extension.               |
| `start`       | Launch the built-in PHP development server. Auto-discovers a free port in 8000-8099 and writes `.env`. |
| `stop`        | Stop the development server.                                                                           |
| `provision`   | Install Drupal on the assembled site and enable the extension.                                         |
| `deploy`      | Mirror the extension to a remote git repository (e.g. drupal.org). Used in CI.                         |
| `browser`     | Start or stop the WebDriver backend used by FunctionalJavascript tests.                                |
| `info`        | Print a summary of the environment, or a single field such as `site-url`, for the wrappers to consume. |
| `qrcode`      | Render a URL as a scannable QR code in the terminal.                                                   |
| `helpers.php` | Shared PHP utilities (dotenv read/write, port discovery, drush wrappers, filesystem helpers).          |

## Verbose output

By default the scripts print only their own `[TASK]`/`[ OK ]` progress and suppress the output of the tools they run (Composer, npm, Drush). When a tool fails, its captured output is shown so the failure is diagnosable. Set `DEBUG=1` to stream the full output of every tool live, for example `DEBUG=1 make build` or `DEBUG=1 ahoy build`.

## Custom scripts

`assemble` and `provision` both look for `scripts/<prefix>-*.sh` in the project root and run any matches at the end of the phase. `assemble-*.sh` for post-assemble, `provision-*.sh` for post-provision. Scripts run in lexicographic order, inherit the parent environment, and a non-zero exit aborts the parent. See the "Custom assemble and provision scripts" section in the root `README.md` for the full convention.

See the root `README.md` for higher-level workflow documentation.
