#!/usr/bin/env bash
##
# Start built-in PHP-server.
#
# shellcheck disable=SC2015,SC2094,SC2002

set -eu
[ -n "${DEBUG:-}" ] && set -x

#-------------------------------------------------------------------------------
# Variables (passed from environment; provided for reference only).
#-------------------------------------------------------------------------------

# Webserver hostname.
WEBSERVER_HOST="${WEBSERVER_HOST:-localhost}"

# Webserver port.
WEBSERVER_PORT="${WEBSERVER_PORT:-8000}"

# Webserver wait timeout.
WEBSERVER_WAIT_TIMEOUT="${WEBSERVER_WAIT_TIMEOUT:-5}"

#-------------------------------------------------------------------------------

echo "-------------------------------"
echo "   Start built-in PHP server   "
echo "-------------------------------"

echo "> Stop previously started services, if any."
killall -9 php >/dev/null 2>&1 || true

echo "> Start the PHP webserver."
nohup php -S "${WEBSERVER_HOST}:${WEBSERVER_PORT}" -t "$(pwd)/build/web" "$(pwd)/build/web/.ht.router.php" >/tmp/php.log 2>&1 &

echo "> Wait ${WEBSERVER_WAIT_TIMEOUT} seconds for the server to be ready."
sleep "${WEBSERVER_WAIT_TIMEOUT}"

echo "> Check that the server was started."
netstat_opts='-tulpn'
[ "$(uname)" == "Darwin" ] && netstat_opts='-anv' || true
netstat "${netstat_opts[@]}" | grep -q "${WEBSERVER_PORT}" || (echo "ERROR: Unable to start inbuilt PHP server" && cat /tmp/php.log && exit 1)

echo "> Check that the server can serve content."
curl -s -o /dev/null -w "%{http_code}" -L -I "http://${WEBSERVER_HOST}:${WEBSERVER_PORT}" | grep -q 200 || (echo "ERROR: Server is started, but site cannot be served" && exit 1)

echo
echo "-----------------------------------"
echo "  Started built-in PHP server 🚀🚀 "
echo "-----------------------------------"
echo
echo "Directory : $(pwd)/build/web"
echo "URL       : http://${WEBSERVER_HOST}:${WEBSERVER_PORT}"
echo
echo "Re-run when you enable or disable XDebug."
echo
echo "> Next steps:"
echo "  .devtools/provision.sh    # Provision the website"
echo
