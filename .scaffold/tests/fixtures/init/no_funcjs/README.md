@@ -169,24 +169,10 @@
 make test-unit                    # Run Unit tests
 make test-kernel                  # Run Kernel tests
 make test-functional              # Run Functional tests
-make test-functional-javascript   # Run FunctionalJavascript tests
 
 ahoy test-unit                    # Run Unit tests
 ahoy test-kernel                  # Run Kernel tests
 ahoy test-functional              # Run Functional tests
-ahoy test-functional-javascript   # Run FunctionalJavascript tests
-```
-
-### Running FunctionalJavascript tests
-
-FunctionalJavascript tests require a browser controlled via WebDriver.
-
-```bash
-ahoy selenium-start
-WEBSERVER_HOST=__VERSION__.0 ahoy start
-ahoy provision
-ahoy test-functional-javascript
-ahoy selenium-stop
 ```
 
 ### Running specific tests
