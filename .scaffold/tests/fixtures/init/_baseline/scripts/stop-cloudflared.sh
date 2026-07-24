#!/usr/bin/env bash
##
# Opt-in pre-stop hook: tear down the Cloudflare quick tunnel.
#
# Kills the `cloudflared` process started by `scripts/start-cloudflared.sh` and
# removes the TUNNEL_URL entry from `.env` so a dead public URL is not reported
# on the next run. Runs even when CLOUDFLARE_TUNNEL is no longer set, so cleanup
# always happens; a no-op when no tunnel is active. The current working
# directory is the project root.

set -eu

pid_file=".logs/cloudflared.pid"

if [ -f "$pid_file" ]; then
  pid="$(cat "$pid_file" 2>/dev/null || true)"
  case "$pid" in
    "" | *[!0-9]*) pid="" ;;
  esac
  # Signal only a PID that is still a cloudflared process, so a recycled PID
  # belonging to another program is never killed.
  if [ -n "$pid" ] && ps -p "$pid" -o command= 2>/dev/null | grep -q cloudflared; then
    kill "$pid" 2>/dev/null || true
    # Wait briefly for it to exit before dropping the pid file.
    for _ in 1 2 3; do
      kill -0 "$pid" 2>/dev/null || break
      sleep 1
    done
    echo "[cloudflared] Tunnel stopped."
  fi
  rm -f "$pid_file"
fi

# Drop TUNNEL_URL from .env so the next run does not report a stale URL.
if [ -f .env ] && grep -q '^TUNNEL_URL=' .env; then
  env_tmp=".env.cloudflared.$$"
  grep -v '^TUNNEL_URL=' .env >"$env_tmp" 2>/dev/null || true
  mv "$env_tmp" .env
fi
