@@ -140,47 +140,11 @@
 make test-unit                    # Run Unit tests
 make test-kernel                  # Run Kernel tests
 make test-functional              # Run Functional tests
-make test-functional-javascript   # Run FunctionalJavascript tests
 
 ahoy test-unit                    # Run Unit tests
 ahoy test-kernel                  # Run Kernel tests
 ahoy test-functional              # Run Functional tests
-ahoy test-functional-javascript   # Run FunctionalJavascript tests
 ```
-
-### Running FunctionalJavascript tests
-
-FunctionalJavascript tests need a real browser driven via WebDriver. By
-default they use the Google Chrome already installed on your machine - a
-matching `chromedriver` is downloaded automatically on first run, so no
-Docker is required:
-
-```bash
-ahoy start
-ahoy provision
-ahoy test-functional-javascript
-ahoy browser-stop
-```
-
-To run the browser in a Docker Selenium container instead, set
-`WEBDRIVER_BACKEND=selenium`. The container cannot reach the host's
-`localhost`, so start the webserver on all interfaces:
-
-```bash
-WEBSERVER_HOST=__VERSION__.0 ahoy start
-ahoy provision
-WEBDRIVER_BACKEND=selenium ahoy test-functional-javascript
-ahoy browser-stop
-```
-
-Either backend gets its own WebDriver port: a free one is claimed starting
-at 4444 and stored in `.env`, so several projects can run FunctionalJavascript
-tests at the same time. Set `WEBDRIVER_PORT` to pin a specific one. Tests
-reach the claimed port because the base class applies it to the WebDriver
-endpoint, so extend `ForceCrystalFunctionalJavascriptTestBase` rather than
-`WebDriverTestBase` directly - a test that bypasses it keeps the default
-endpoint from `phpunit.xml` unless it exports its own
-`MINK_DRIVER_ARGS_WEBDRIVER`.
 
 ### Running specific tests
 
