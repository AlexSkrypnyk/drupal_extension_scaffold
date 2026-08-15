@@ -43,10 +43,7 @@
 - `ahoy test-unit` - Run unit tests only
 - `ahoy test-kernel` - Run kernel tests only
 - `ahoy test-functional` - Run functional tests only
-- `ahoy test-functional-javascript` - Run FunctionalJavascript tests (uses the local Chrome by default; set `WEBDRIVER_BACKEND=selenium` for Docker)
 - `ahoy test-javascript` - Run JavaScript unit tests with Jest (alias: `ahoy test-js`)
-- `ahoy browser-start` - Start the browser for FunctionalJavascript tests (local Chrome by default; set `WEBDRIVER_BACKEND=selenium` for Docker)
-- `ahoy browser-stop` - Stop the browser
 
 ### Drupal Commands
 
@@ -83,8 +80,6 @@
 - `DRUPAL_VERSION` - Target Drupal version (e.g., `10`, `11`, `11@alpha`)
 - `WEBSERVER_HOST` - Development server host (default: localhost)
 - `WEBSERVER_PORT` - Development server port. Auto-discovered from range 8000-8099 and written to `.env` if not already set
-- `WEBDRIVER_BACKEND` - FunctionalJavascript WebDriver backend: `chromedriver` (default, drives the locally installed Chrome with no Docker) or `selenium` (Docker container)
-- `WEBDRIVER_PORT` - Port for the WebDriver endpoint (both backends). Auto-discovered from 4444 and written to `.env` if not already set, so several projects can run FunctionalJavascript tests simultaneously. The endpoint in `phpunit.xml` is the default for port 4444; tests reach the resolved port because the FunctionalJavascript base class rewrites the port in `MINK_DRIVER_ARGS_WEBDRIVER` from this variable, so FunctionalJavascript tests must extend that base class (or export their own `MINK_DRIVER_ARGS_WEBDRIVER`)
 - `GITHUB_TOKEN` - GitHub API token to avoid rate limits
 - `DEBUG` - Set to `1` to stream the full output of the underlying commands (Composer, npm, Drush). By default this output is suppressed and shown only when a command fails
 
