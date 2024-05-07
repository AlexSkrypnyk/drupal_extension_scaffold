#!/usr/bin/env bash

workflow_prepare_cleanup() {
  local dir="${1:-$(pwd)}"

  pushd "${dir}" >/dev/null || exit 1

  killall -9 php >/dev/null 2>&1 || true
  sleep 1
  chmod -Rf 777 build/sites/default > /dev/null

  popd >/dev/null || exit 1
}
