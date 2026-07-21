#!/usr/bin/env bash
##
# Opt-in pre-stop hook: tear down the Cloudflare quick tunnel.
#
# Kills the `cloudflared` process started by `scripts/start-cloudflared.sh` and
# removes the TUNNEL_URL entry from `.env` so a dead public URL is not reported
# on the next run. Intentionally ungated so cleanup runs even when
# CLOUDFLARE_TUNNEL is no longer set; a no-op when no tunnel is active. CWD is
# the project root.

set -eu

pid_file=".logs/cloudflared.pid"

if [ -f "$pid_file" ]; then
  kill "$(cat "$pid_file")" 2>/dev/null || true
  rm -f "$pid_file"
  echo "[cloudflared] Tunnel stopped."
fi

# Drop TUNNEL_URL from .env so the next run does not report a stale URL.
if [ -f .env ] && grep -q '^TUNNEL_URL=' .env; then
  env_tmp=".env.cloudflared.$$"
  grep -v '^TUNNEL_URL=' .env >"$env_tmp" 2>/dev/null || true
  mv "$env_tmp" .env
fi
