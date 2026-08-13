#!/usr/bin/env bash
##
# Example pre-stop script.
#
# Runs during `.devtools/stop` before the PHP webserver is stopped, while it
# is still reachable. The current working directory is the project root. Any
# non-zero exit aborts the parent stop run.
#
# Custom logic goes in any file matching `scripts/stop-*.sh` - all matching
# files run in lexicographic order. Teardown for whatever a matching
# `start-*.sh` script launched belongs here.

set -eu

echo "[example] pre-stop script ran."
