#!/usr/bin/make -f

WEBSERVER_HOST ?= localhost
WEBSERVER_PORT ?= 8000

.PHONY: *

# Build or rebuild the project.
build: stop assemble start provision

# Stop development environment.
stop:
	./.devtools/stop.sh

# Assemble a codebase using project code and all required dependencies.
assemble:
	./.devtools/assemble.sh

# Start development environment.
start:
	./.devtools/start.sh

# Provision application within assembled codebase.
provision:
	./.devtools/provision.sh

# Check coding standards for violations.
lint:
	pushd "build" >/dev/null || exit 1 && \
	vendor/bin/phpcs && \
	vendor/bin/phpstan && \
	vendor/bin/rector --clear-cache --dry-run && \
	vendor/bin/phpmd . text phpmd.xml && \
	vendor/bin/twig-cs-fixer && \
	popd >/dev/null || exit 1

# Fix violations in coding standards.
lint-fix:
	pushd "build" >/dev/null || exit 1 && \
	vendor/bin/rector --clear-cache && \
	vendor/bin/phpcbf && \
	popd >/dev/null || exit 1

# Run tests.
test:
	pushd "build" >/dev/null || exit 1 && \
	vendor/bin/phpunit && \
	popd >/dev/null || exit 1

# Run unit tests.
test-unit:
	pushd "build" >/dev/null || exit 1 && \
	vendor/bin/phpunit --testsuite unit && \
	popd >/dev/null || exit 1

# Run kernel tests.
test-kernel:
	pushd "build" >/dev/null || exit 1 && \
	vendor/bin/phpunit --testsuite kernel && \
	popd >/dev/null || exit 1

# Run functional tests.
test-functional:
	pushd "build" >/dev/null || exit 1 && \
	vendor/bin/phpunit --testsuite functional && \
	popd >/dev/null || exit 1

# Reset project to the default state.
reset:
	killall -9 php >/dev/null 2>&1 || true && \
	chmod -Rf 777 build > /dev/null && \
	rm -Rf build > /dev/null || true && \
	rm -Rf .logs > /dev/null || true

# Run Drush login command.
login:
	build/vendor/bin/drush -l http://${WEBSERVER_HOST}:${WEBSERVER_PORT} uli

# Run Drush status command.
status:
	build/vendor/bin/drush -l http://${WEBSERVER_HOST}:${WEBSERVER_PORT} status
