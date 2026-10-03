#!/usr/bin/env bash
##
# Opt-in post-provision hook: make Drupal serve correctly behind the tunnel.
#
# Activates only when CLOUDFLARE_TUNNEL is truthy. Appends reverse-proxy and
# trusted-host settings to the freshly installed site's settings.php so Drupal
# trusts Cloudflare's X-Forwarded-* headers (correct HTTPS detection) and
# accepts the `*.trycloudflare.com` hostname. Idempotent: the block is written
# once per settings.php.
#
# This lives in a provision hook rather than the start hook because
# `drush site-install` rewrites settings.php during provision, which runs after
# start. Pairs with `scripts/start-cloudflared.sh`. The current working
# directory is the project root.

set -eu

case "${CLOUDFLARE_TUNNEL:-}" in
  "" | 0 | false | no | off) exit 0 ;;
esac

settings="build/web/sites/default/settings.php"
marker="# Cloudflare quick tunnel settings."

if [ ! -f "$settings" ]; then
  echo "[cloudflared] ${settings} not found; skipping tunnel settings."
  exit 0
fi

if grep -qF "$marker" "$settings"; then
  exit 0
fi

# The installer leaves settings.php read-only; restore write access to append.
chmod u+w "$settings"

cat >>"$settings" <<'PHP'

# Cloudflare quick tunnel settings.
$settings['reverse_proxy'] = TRUE;
$settings['reverse_proxy_addresses'] = ['127.0.0.1', '::1'];
$settings['trusted_host_patterns'][] = '^[a-z0-9-]+\.trycloudflare\.com$';
PHP

echo "[cloudflared] Wrote reverse-proxy/trusted-host settings to ${settings}."
