@@ -178,17 +178,6 @@
 ahoy test-functional-javascript   # Run FunctionalJavascript tests
 ```
 
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
-```
 
 ### Running specific tests
 
