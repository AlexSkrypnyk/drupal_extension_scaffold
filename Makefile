SHELL=/bin/bash
WEBSERVER_HOST ?= localhost
WEBSERVER_PORT ?= 8000

.PHONY: assemble, build, help, lint, lint-fix, login, provision, reset, start, status, stop, test, test-functional, test-kernel, test-unit

help:
	@echo "COMMANDS"
	@echo "========"
	@echo "build           - Build or rebuild the project."
	@echo "assemble        - Assemble a codebase using project code and all required dependencies."
	@echo "drush           - Run Drush command."
	@echo "lint            - Check coding standards for violations."
	@echo "lint-fix        - Fix violations in coding standards."
	@echo "login           - Run Drush login command."
	@echo "provision       - Provision application within assembled codebase."
	@echo "reset           - Reset project to the default state."
	@echo "start           - Start development environment."
	@echo "stop            - Stop development environment."
	@echo "test            - Run all tests."
	@echo "test-functional - Run functional tests."
	@echo "test-kernel     - Run kernel tests."
	@echo "test-unit       - Run unit tests."
	@echo ""
	@echo "Start by running \"make build\""

build: stop assemble start provision

assemble:
	./.devtools/assemble.sh

start:
	./.devtools/start.sh

stop:
	./.devtools/stop.sh

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
	./.devtools/provision.sh

lint:
	pushd "build" >/dev/null || exit 1 && \
	vendor/bin/phpcs && \
	vendor/bin/phpstan && \
	vendor/bin/rector --clear-cache --dry-run && \
	vendor/bin/phpmd . text phpmd.xml && \
	vendor/bin/twig-cs-fixer && \
	popd >/dev/null || exit 1

lint-fix:
	pushd "build" >/dev/null || exit 1 && \
	vendor/bin/rector --clear-cache && \
	vendor/bin/phpcbf && \
	popd >/dev/null || exit 1

test:
	pushd "build" >/dev/null || exit 1 && \
	vendor/bin/phpunit && \
	popd >/dev/null || exit 1

test-unit:
	pushd "build" >/dev/null || exit 1 && \
	vendor/bin/phpunit --testsuite unit && \
	popd >/dev/null || exit 1

test-kernel:
	pushd "build" >/dev/null || exit 1 && \
	vendor/bin/phpunit --testsuite kernel && \
	popd >/dev/null || exit 1

test-functional:
	pushd "build" >/dev/null || exit 1 && \
	vendor/bin/phpunit --testsuite functional && \
	popd >/dev/null || exit 1

reset:
	killall -9 php >/dev/null 2>&1 || true && \
	chmod -Rf 777 build > /dev/null && \
	rm -Rf build > /dev/null || true && \
	rm -Rf .logs > /dev/null || true

.DEFAULT_GOAL := build
