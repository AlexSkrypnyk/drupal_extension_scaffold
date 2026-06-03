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

define title
	@echo -e "\n\033[36m$(1)\033[0m"
endef

.PHONY: assemble build debug debug-off debug-on delete describe destroy help info lint lint-fix login provision reset selenium-start selenium-stop start stop test test-functional test-functional-javascript test-js test-kernel test-unit xdebug xdebug-off xdebug-on

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
	@echo "test-functional            - Run functional tests."
	@echo "test-functional-javascript - Run FunctionalJavascript tests."
	@echo "test-kernel                - Run kernel tests."
	@echo "test-unit                  - Run unit tests."
	@echo "selenium-start             - Start Selenium container."
	@echo "selenium-stop              - Stop Selenium container."
	@echo "test-js                    - Run JavaScript unit tests."

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
	build/vendor/bin/drush -l http://$(WEBSERVER_HOST):$(WEBSERVER_PORT) $(DRUSH_RUN_ARGS)

login:
	build/vendor/bin/drush -l http://$(WEBSERVER_HOST):$(WEBSERVER_PORT) uli

provision:
	./.devtools/provision

lint:
	$(call title,Running PHPCS)
	pushd "build" >/dev/null || exit 1 && vendor/bin/phpcs && popd >/dev/null || exit 1
	$(call title,Running PHPStan)
	pushd "build" >/dev/null || exit 1 && vendor/bin/phpstan && popd >/dev/null || exit 1
	$(call title,Running Rector)
	pushd "build" >/dev/null || exit 1 && vendor/bin/rector --clear-cache --dry-run && popd >/dev/null || exit 1
	$(call title,Running Twig CS Fixer)
	pushd "build" >/dev/null || exit 1 && vendor/bin/twig-cs-fixer && popd >/dev/null || exit 1
	$(call title,Running ESLint)
	pushd "build" >/dev/null || exit 1 && ([ ! -d node_modules ] || npm run lint) && popd >/dev/null || exit 1
	$(call title,Running CSpell)
	[ -d node_modules ] || npm install --no-audit --no-fund
	npm run lint-spell

lint-fix:
	$(call title,Running Rector)
	pushd "build" >/dev/null || exit 1 && vendor/bin/rector --clear-cache && popd >/dev/null || exit 1
	$(call title,Running PHPCBF)
	pushd "build" >/dev/null || exit 1 && vendor/bin/phpcbf && popd >/dev/null || exit 1
	$(call title,Running Twig CS Fixer)
	pushd "build" >/dev/null || exit 1 && vendor/bin/twig-cs-fixer --no-cache --fix && popd >/dev/null || exit 1
	$(call title,Running ESLint)
	pushd "build" >/dev/null || exit 1 && ([ ! -d node_modules ] || npm run lint-fix) && popd >/dev/null || exit 1

test:
	$(call title,Running PHPUnit)
	pushd "build" >/dev/null || exit 1 && BROWSERTEST_OUTPUT_DIRECTORY=/tmp php -d pcov.directory=.. vendor/bin/phpunit && popd >/dev/null || exit 1
	$(call title,Running Jest)
	pushd "build" >/dev/null || exit 1 && ([ ! -d node_modules ] || npm test) && popd >/dev/null || exit 1

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
	BROWSERTEST_OUTPUT_DIRECTORY=/tmp php -d pcov.directory=.. vendor/bin/phpunit --testsuite functional && \
	popd >/dev/null || exit 1

test-functional-javascript: selenium-start
	pushd "build" >/dev/null || exit 1 && \
	BROWSERTEST_OUTPUT_DIRECTORY=/tmp php -d pcov.directory=.. vendor/bin/phpunit --testsuite functional-javascript && \
	popd >/dev/null || exit 1

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

test-js:
	pushd "build" >/dev/null || exit 1 && \
	([ ! -d node_modules ] || npm test) && \
	popd >/dev/null || exit 1

reset:
	killall -9 php >/dev/null 2>&1 || true
	chmod -Rf 777 build .logs > /dev/null 2>&1 || true
	rm -Rf build > /dev/null 2>&1 || true
	rm -Rf .logs > /dev/null 2>&1 || true

.DEFAULT_GOAL := build
