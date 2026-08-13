#!/usr/bin/env bash
##
# Example post-start script.
#
# Runs at the end of `.devtools/start` (after the PHP webserver is up and
# serving). The current working directory is the project root. Any non-zero
# exit aborts the parent start run.
#
# Custom logic goes in any file matching `scripts/start-*.sh` - all matching
# files run in lexicographic order. Server-lifecycle tasks such as launching
# an access tunnel or a file watcher belong here.

set -eu

echo "[example] post-start script ran."
