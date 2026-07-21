@@ -43,12 +43,7 @@
 - `ahoy test-unit` - Run unit tests only
 - `ahoy test-kernel` - Run kernel tests only
 - `ahoy test-functional` - Run functional tests only
-- `ahoy test-functional-javascript` - Run FunctionalJavascript tests (uses the local Chrome by default; set `WEBDRIVER_BACKEND=selenium` for Docker)
 - `ahoy test-js` - Run JavaScript unit tests (Jest)
-- `ahoy chromedriver-start` - Start chromedriver against the locally installed Chrome (default backend)
-- `ahoy chromedriver-stop` - Stop chromedriver
-- `ahoy selenium-start` - Start Selenium container
-- `ahoy selenium-stop` - Stop Selenium container
 
 ### Drupal Commands
 
@@ -85,7 +80,6 @@
 - `DRUPAL_VERSION` - Target Drupal version (e.g., `10`, `11`, `11@alpha`)
 - `WEBSERVER_HOST` - Development server host (default: localhost)
 - `WEBSERVER_PORT` - Development server port. Auto-discovered from range 8000-8099 and written to `.env` if not already set
-- `WEBDRIVER_BACKEND` - FunctionalJavascript WebDriver backend: `chromedriver` (default, drives the locally installed Chrome with no Docker) or `selenium` (Docker container)
 - `GITHUB_TOKEN` - GitHub API token to avoid rate limits
 - `DEBUG` - Set to `1` to stream the full output of the underlying commands (Composer, npm, Drush). By default this output is suppressed and shown only when a command fails
 
