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
# (see resolve_site_url()). Lazy `=` so the probe runs only when the recipes
# that expand it are invoked.
DRUSH = build/vendor/bin/drush -l "$(shell ./.devtools/info site-url)"

# Test environment exported to every recipe (see the blanket `export` above),
# matching what the ahoy entrypoint exports for every command, so `make
# test*` runs against the same base URL, database, and browser-output
# directory as `ahoy test*`.
EXTENSION_NAME = $(shell basename -s .info.yml -- ./*.info.yml)
SIMPLETEST_BASE_URL = http://$(WEBSERVER_HOST):$(WEBSERVER_PORT)
SIMPLETEST_DB = sqlite://localhost/drupal_test_$(EXTENSION_NAME).sqlite
BROWSERTEST_OUTPUT_DIRECTORY = $(CURDIR)/.logs/browser_output

define title
	@echo -e "\n\033[36m$(1)\033[0m"
endef

.PHONY: assemble build debug debug-off debug-on delete describe destroy drush help info lint lint-fix login provision reset start stop test xdebug xdebug-off xdebug-on

help:
	@echo "COMMANDS"
	@echo "========"
	@echo "build                      - Build or rebuild the project."
	@echo "assemble                   - Assemble a codebase using project code and all required dependencies."
	@echo "debug                      - Enable PHP XDebug step-debugging for the development server (aliases: debug-on, xdebug, xdebug-on)."
	@echo "drush                      - Run Drush command."
	@echo "info                       - Print a read-only summary of the current environment (alias: describe)."
	@echo "lint                       - Check coding standards for violations."
	@echo "lint-fix                   - Fix violations in coding standards."
	@echo "login                      - Login to a website."
	@echo "provision                  - Provision application within assembled codebase."
	@echo "reset                      - Reset project to the default state (aliases: delete, destroy)."
	@echo "start                      - Start development environment (aliases: debug-off, xdebug-off)."
	@echo "stop                       - Stop development environment."
	@echo "test                       - Run all tests."

build:
	@$(MAKE) stop >/dev/null 2>&1 || true
	$(MAKE) assemble
	$(MAKE) start
	$(MAKE) provision

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
	$(DRUSH) $(DRUSH_RUN_ARGS)

login:
	@url="$$($(DRUSH) uli)"; printf '%s\n' "$$url"; if [ -n "$$LOGIN_QRCODE" ]; then ./.devtools/qrcode "$$url"; fi

provision:
	./.devtools/provision

lint:

lint-fix:

# Allow passing extra args to phpunit test targets, mirroring the `drush`
# arg-capture above. The target list is split across the same DEV_* markers
# as the `.PHONY` declarations so a stripped feature leaves no dangling entry.
TEST_TARGETS := test
ifneq (,$(filter $(firstword $(MAKECMDGOALS)),$(TEST_TARGETS)))
  TEST_RUN_ARGS := $(wordlist 2,$(words $(MAKECMDGOALS)),$(MAKECMDGOALS))
  $(eval $(TEST_RUN_ARGS):;@:)
endif

test:





reset:
	killall -9 php >/dev/null 2>&1 || true
	chmod -Rf 777 build .logs > /dev/null 2>&1 || true
	rm -Rf build > /dev/null 2>&1 || true
	rm -Rf .logs > /dev/null 2>&1 || true

.DEFAULT_GOAL := build
