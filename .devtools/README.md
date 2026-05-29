This directory contains scripts used for development. These can be used locally and in the CI environment.

| Script        | Purpose                                                                                                |
|---------------|--------------------------------------------------------------------------------------------------------|
| `assemble`    | Assemble a Drupal codebase in `build/`, install dependencies, and symlink the extension.               |
| `start`       | Launch the built-in PHP development server. Auto-discovers a free port in 8000-8099 and writes `.env`. |
| `stop`        | Stop the development server.                                                                           |
| `provision`   | Install Drupal on the assembled site and enable the extension.                                         |
| `deploy`      | Mirror the extension to a remote git repository (e.g. drupal.org). Used in CI.                         |
| `helpers.php` | Shared PHP utilities (dotenv read/write, port discovery, drush wrappers, filesystem helpers).          |

See the root `README.md` for higher-level workflow documentation.
