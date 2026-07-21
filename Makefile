SHELL=/bin/bash

# Load variables from .env if present and export them to recipe shells. The
# leading '-' on -include suppresses errors when the file does not exist.
-include .env
export

# Resolve a relative TMPDIR to an absolute path so build/-dir tools (which
# chdir into build) can still find it. No-op when unset or already absolute.
ifdef TMPDIR
export TMPDIR := $(abspath $(TMPDIR))
endif

WEBSERVER_HOST ?= localhost
WEBSERVER_PORT ?= 8000

# Resolve the site URL through the shared `.devtools/info` resolver so `drush`
# and `login` report the same tunnel-aware URL as start, provision, and info
# (see resolve_site_url()). Lazy `=` so the probe runs only when drush/login
# are invoked.
DRUSH_URI = $(shell ./.devtools/info site-url)

define title
	@echo -e "\n\033[36m$(1)\033[0m"
endef

.PHONY: assemble build debug debug-off debug-on delete describe destroy help info lint lint-fix login provision reset start stop test xdebug xdebug-off xdebug-on
#;< DEV_PHPUNIT
.PHONY: test-unit test-kernel test-functional
#;> DEV_PHPUNIT
#;< DEV_FUNCTIONAL_JAVASCRIPT
.PHONY: test-functional-javascript chromedriver-start chromedriver-stop selenium-start selenium-stop
#;> DEV_FUNCTIONAL_JAVASCRIPT
#;< DEV_JEST
.PHONY: test-js
#;> DEV_JEST

help:
	@echo "COMMANDS"
	@echo "========"
	@echo "build           - Build or rebuild the project."
	@echo "assemble        - Assemble a codebase using project code and all required dependencies."
	@echo "debug           - Enable PHP XDebug step-debugging for the development server."
	@echo "drush           - Run Drush command."
	@echo "info            - Print a read-only summary of the current environment (alias: describe)."
	@echo "lint            - Check coding standards for violations."
	@echo "lint-fix        - Fix violations in coding standards."
	@echo "login           - Run Drush login command."
	@echo "provision       - Provision application within assembled codebase."
	@echo "reset           - Reset project to the default state (aliases: delete, destroy)."
	@echo "start           - Start development environment."
	@echo "stop            - Stop development environment."
	@echo "test                       - Run all tests."
	@#;< DEV_PHPUNIT
	@echo "test-functional            - Run functional tests."
	@#;> DEV_PHPUNIT
	@#;< DEV_FUNCTIONAL_JAVASCRIPT
	@echo "test-functional-javascript - Run FunctionalJavascript tests."
	@#;> DEV_FUNCTIONAL_JAVASCRIPT
	@#;< DEV_PHPUNIT
	@echo "test-kernel                - Run kernel tests."
	@echo "test-unit                  - Run unit tests."
	@#;> DEV_PHPUNIT
	@#;< DEV_FUNCTIONAL_JAVASCRIPT
	@echo "chromedriver-start         - Start chromedriver against the local Chrome."
	@echo "chromedriver-stop          - Stop chromedriver."
	@echo "selenium-start             - Start Selenium container."
	@echo "selenium-stop              - Stop Selenium container."
	@#;> DEV_FUNCTIONAL_JAVASCRIPT
	@#;< DEV_JEST
	@echo "test-js                    - Run JavaScript unit tests."
	@#;> DEV_JEST

build: stop assemble start provision

assemble:
	./.devtools/assemble

start:
	./.devtools/start

stop:
	./.devtools/stop

info:
	@./.devtools/info

# Enable PHP XDebug step-debugging by restarting the PHP server with
# `-d xdebug.mode=debug -d xdebug.start_with_request=yes`. State is
# probed by `info xdebug`, which inspects the running server's command
# line. Run `make start` to disable.
debug:
	@[ "$$(./.devtools/info xdebug)" = "enabled" ] && echo "XDebug is already enabled. Run 'make start' to disable." || \
		(XDEBUG=1 ./.devtools/start && sleep 1 && [ "$$(./.devtools/info xdebug)" = "enabled" ] && echo "Enabled XDebug. Run 'make start' to disable." || (echo "Failed to enable XDebug." && exit 1))

# Make has no native command aliases - the alias targets declare `debug` as
# their sole prerequisite, so running e.g. `make xdebug` executes the `debug`
# recipe via the prerequisite chain.
debug-on xdebug xdebug-on: debug

# Mirror the ahoy `start` aliases. `make debug-off` runs the `start` recipe
# via the prerequisite chain, which restarts without XDebug.
debug-off xdebug-off: start

# DDEV uses `describe` for the equivalent of our `info`. Add the alias so
# developers coming from DDEV find a familiar verb.
describe: info

# Lando uses `destroy` and DDEV uses `delete` for what our `reset` does.
delete destroy: reset

# Allow running Drush commands with `make drush <command>`
ifeq (drush,$(firstword $(MAKECMDGOALS)))
  DRUSH_RUN_ARGS := $(wordlist 2,$(words $(MAKECMDGOALS)),$(MAKECMDGOALS))
  $(eval $(DRUSH_RUN_ARGS):;@:)
endif

drush:
	build/vendor/bin/drush -l $(DRUSH_URI) $(DRUSH_RUN_ARGS)

login:
	@url="$$(build/vendor/bin/drush -l $(DRUSH_URI) uli)"; printf '%s\n' "$$url"; ./.devtools/qrcode "$$url"

provision:
	./.devtools/provision

lint:
	@#;< DEV_PHPCS
	$(call title,Running PHPCS)
	pushd "build" >/dev/null || exit 1 && vendor/bin/phpcs && popd >/dev/null || exit 1
	@#;> DEV_PHPCS
	@#;< DEV_PHPSTAN
	$(call title,Running PHPStan)
	pushd "build" >/dev/null || exit 1 && vendor/bin/phpstan && popd >/dev/null || exit 1
	@#;> DEV_PHPSTAN
	@#;< DEV_RECTOR
	$(call title,Running Rector)
	pushd "build" >/dev/null || exit 1 && vendor/bin/rector --clear-cache --dry-run && popd >/dev/null || exit 1
	@#;> DEV_RECTOR
	@#;< DEV_TWIGCS
	$(call title,Running Twig CS Fixer)
	pushd "build" >/dev/null || exit 1 && vendor/bin/twig-cs-fixer && popd >/dev/null || exit 1
	@#;> DEV_TWIGCS
	@#;< DEV_NODEJS_LINT
	$(call title,Running ESLint)
	pushd "build" >/dev/null || exit 1 && ([ ! -d node_modules ] || npm run lint) && popd >/dev/null || exit 1
	@#;> DEV_NODEJS_LINT
	@#;< DEV_CSPELL
	$(call title,Running CSpell)
	[ -d node_modules ] || npm install --no-audit --no-fund
	npm run lint-spell
	@#;> DEV_CSPELL

lint-fix:
	@#;< DEV_RECTOR
	$(call title,Running Rector)
	pushd "build" >/dev/null || exit 1 && vendor/bin/rector --clear-cache && popd >/dev/null || exit 1
	@#;> DEV_RECTOR
	@#;< DEV_PHPCS
	$(call title,Running PHPCBF)
	pushd "build" >/dev/null || exit 1 && vendor/bin/phpcbf && popd >/dev/null || exit 1
	@#;> DEV_PHPCS
	@#;< DEV_TWIGCS
	$(call title,Running Twig CS Fixer)
	pushd "build" >/dev/null || exit 1 && vendor/bin/twig-cs-fixer --no-cache --fix && popd >/dev/null || exit 1
	@#;> DEV_TWIGCS
	@#;< DEV_NODEJS_LINT
	$(call title,Running ESLint)
	pushd "build" >/dev/null || exit 1 && ([ ! -d node_modules ] || npm run lint-fix) && popd >/dev/null || exit 1
	@#;> DEV_NODEJS_LINT

test:
	@#;< DEV_PHPUNIT
	$(call title,Running PHPUnit)
	pushd "build" >/dev/null || exit 1 && BROWSERTEST_OUTPUT_DIRECTORY=$(CURDIR)/.logs/browser_output php -d pcov.directory=.. vendor/bin/phpunit && popd >/dev/null || exit 1
	@#;> DEV_PHPUNIT
	@#;< DEV_JEST
	$(call title,Running Jest)
	pushd "build" >/dev/null || exit 1 && ([ ! -d node_modules ] || npm test) && popd >/dev/null || exit 1
	@#;> DEV_JEST

#;< DEV_PHPUNIT
test-unit:
	pushd "build" >/dev/null || exit 1 && \
	php -d pcov.directory=.. vendor/bin/phpunit --testsuite unit && \
	popd >/dev/null || exit 1

test-kernel:
	pushd "build" >/dev/null || exit 1 && \
	php -d pcov.directory=.. vendor/bin/phpunit --testsuite kernel && \
	popd >/dev/null || exit 1

test-functional:
	pushd "build" >/dev/null || exit 1 && \
	BROWSERTEST_OUTPUT_DIRECTORY=$(CURDIR)/.logs/browser_output php -d pcov.directory=.. vendor/bin/phpunit --testsuite functional && \
	popd >/dev/null || exit 1
#;> DEV_PHPUNIT

#;< DEV_FUNCTIONAL_JAVASCRIPT
test-functional-javascript:
	@if [ "$${WEBDRIVER_BACKEND:-chromedriver}" = "selenium" ]; then $(MAKE) selenium-start; else $(MAKE) chromedriver-start; fi
	pushd "build" >/dev/null || exit 1 && \
	BROWSERTEST_OUTPUT_DIRECTORY=$(CURDIR)/.logs/browser_output php -d pcov.directory=.. vendor/bin/phpunit --testsuite functional-javascript && \
	popd >/dev/null || exit 1
#;> DEV_FUNCTIONAL_JAVASCRIPT

#;< DEV_FUNCTIONAL_JAVASCRIPT
chromedriver-start:
	@if curl -s http://localhost:4444/status | grep -qE '"ready": ?true'; then \
		echo "WebDriver is already running on port 4444."; \
	else \
		if [ -x "/Applications/Google Chrome.app/Contents/MacOS/Google Chrome" ]; then \
			chrome_version=$$("/Applications/Google Chrome.app/Contents/MacOS/Google Chrome" --version); \
		elif command -v google-chrome >/dev/null 2>&1; then \
			chrome_version=$$(google-chrome --version); \
		elif command -v chromium >/dev/null 2>&1; then \
			chrome_version=$$(chromium --version); \
		elif command -v chromium-browser >/dev/null 2>&1; then \
			chrome_version=$$(chromium-browser --version); \
		else \
			echo "ERROR: Google Chrome or Chromium was not found. Install Chrome, or set WEBDRIVER_BACKEND=selenium to use Docker."; \
			exit 1; \
		fi; \
		chrome_version=$$(echo "$$chrome_version" | grep -oE '[0-9]+(\.[0-9]+){3}' | head -1); \
		mkdir -p .chromedriver; \
		echo "Fetching chromedriver matching Chrome $$chrome_version..."; \
		chromedriver_bin=$$(npx -y @puppeteer/browsers install "chromedriver@$$chrome_version" --path .chromedriver | awk 'END {print $$NF}'); \
		nohup "$$chromedriver_bin" --port=4444 >.chromedriver/chromedriver.log 2>&1 & \
		echo "Waiting for chromedriver to be ready..."; \
		for i in $$(seq 1 30); do curl -s http://localhost:4444/status | grep -qE '"ready": ?true' && break; sleep 1; done; \
		if ! curl -s http://localhost:4444/status | grep -qE '"ready": ?true'; then \
			echo "ERROR: chromedriver failed to become ready after 30 seconds."; \
			exit 1; \
		fi; \
	fi

chromedriver-stop:
	pkill -f "chromedriver --port=4444" 2>/dev/null || true

selenium-start:
	@if curl -s http://localhost:4444/status | grep -q '"ready": true'; then \
		echo "Selenium container is already running."; \
	else \
		docker rm -f selenium 2>/dev/null || true; \
		docker run -d --name selenium -p 4444:4444 selenium/standalone-chromium:latest; \
		echo "Waiting for Selenium to be ready..."; \
		for i in $$(seq 1 30); do curl -s http://localhost:4444/status | grep -q '"ready": true' && break; sleep 1; done; \
		if ! curl -s http://localhost:4444/status | grep -q '"ready": true'; then \
			echo "ERROR: Selenium failed to become ready after 30 seconds."; \
			exit 1; \
		fi; \
	fi

selenium-stop:
	docker rm -f selenium 2>/dev/null || true
#;> DEV_FUNCTIONAL_JAVASCRIPT

#;< DEV_JEST
test-js:
	pushd "build" >/dev/null || exit 1 && \
	([ ! -d node_modules ] || npm test) && \
	popd >/dev/null || exit 1
#;> DEV_JEST

reset:
	killall -9 php >/dev/null 2>&1 || true
	chmod -Rf 777 build .logs > /dev/null 2>&1 || true
	rm -Rf build > /dev/null 2>&1 || true
	rm -Rf .logs > /dev/null 2>&1 || true

.DEFAULT_GOAL := build
