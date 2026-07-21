#!/usr/bin/env bash
##
# Opt-in post-start hook: expose the dev server via a Cloudflare quick tunnel.
#
# Activates only when CLOUDFLARE_TUNNEL is truthy and the `cloudflared` binary
# is on PATH. Starts (or reuses a healthy) quick tunnel pointing at the local
# PHP webserver and writes the public HTTPS URL to `.env` as TUNNEL_URL, which
# `.devtools/start`, `provision`, and `info` then display and which
# `make`/`ahoy drush` and `login` pass to Drush.
#
# No Cloudflare account, DNS, or config is needed - quick tunnels mint an
# ephemeral `*.trycloudflare.com` hostname. Install `cloudflared` via mise,
# brew, or apt. Remove or rename this file to disable. CWD is the project root.

set -eu

# Skip unless CLOUDFLARE_TUNNEL opts in.
case "${CLOUDFLARE_TUNNEL:-}" in
  "" | 0 | false | no | off) exit 0 ;;
esac

if ! command -v cloudflared >/dev/null 2>&1; then
  echo "[cloudflared] Not found on PATH; skipping tunnel."
  exit 0
fi

port="${WEBSERVER_PORT:-8000}"
url_pattern='https://[a-z0-9-]+\.trycloudflare\.com'
mkdir -p .logs
pid_file=".logs/cloudflared.pid"
log_file=".logs/cloudflared.log"

# Reuse an existing tunnel only when its process is alive AND its public URL
# answers - a live process is not proof of a reachable tunnel, as quick tunnels
# can drop their edge connection while cloudflared keeps running.
if [ -f "$pid_file" ] && kill -0 "$(cat "$pid_file")" 2>/dev/null; then
  existing_url="$(grep -oE "$url_pattern" "$log_file" 2>/dev/null | tail -1 || true)"
  if [ -n "$existing_url" ] && curl -sf -o /dev/null -m 5 "$existing_url"; then
    echo "[cloudflared] Reusing healthy tunnel: $existing_url"
    exit 0
  fi
  echo "[cloudflared] Existing tunnel unhealthy; restarting."
  kill "$(cat "$pid_file")" 2>/dev/null || true
fi

echo "[cloudflared] Starting quick tunnel for http://localhost:${port}"
nohup cloudflared tunnel --url "http://localhost:${port}" --no-autoupdate >"$log_file" 2>&1 &
echo $! >"$pid_file"

url=""
for _ in $(seq 1 30); do
  url="$(grep -oE "$url_pattern" "$log_file" 2>/dev/null | head -1 || true)"
  [ -n "$url" ] && break
  sleep 1
done

if [ -z "$url" ]; then
  echo "[cloudflared] Tunnel URL not available yet; see ${log_file}."
  exit 0
fi

# Persist TUNNEL_URL to .env, replacing any prior line (last-assignment-wins).
touch .env
env_tmp=".env.cloudflared.$$"
grep -v '^TUNNEL_URL=' .env >"$env_tmp" 2>/dev/null || true
echo "TUNNEL_URL=${url}" >>"$env_tmp"
mv "$env_tmp" .env

echo "[cloudflared] Public URL: ${url}"
