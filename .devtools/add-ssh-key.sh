#!/usr/bin/env bash
##
# Add ssh key.
#
# - adds deployment SSH key to SSH agent
#
# It is a good practice to create a separate Deployer user with own SSH key for
# every project.

set -eu
[ -n "${DEBUG:-}" ] && set -x

# The fingerprint of the SSH key of the user on behalf of which the deployment
# is performed.
DEPLOY_SSH_FINGERPRINT="${DEPLOY_SSH_FINGERPRINT:-}"

[ -z "${DEPLOY_SSH_FINGERPRINT}" ] && echo "ERROR: Missing required value for DEPLOY_SSH_FINGERPRINT" && exit 1

# echo "> Configure SSH to connect to remote servers for deployment."
ls -al "${HOME}/.ssh/"
mkdir -p "${HOME}/.ssh/"
echo -e "Host *\n\tStrictHostKeyChecking no\n" >"${HOME}/.ssh/config"
DEPLOY_SSH_FILE="${DEPLOY_SSH_FINGERPRINT//:/}"
DEPLOY_SSH_FILE="${HOME}/.ssh/id_rsa"
[ ! -f "${DEPLOY_SSH_FILE:-}" ] && echo "ERROR: Unable to find Deploy SSH key file ${DEPLOY_SSH_FILE}." && exit 1
if [ -z "${SSH_AGENT_PID:-}" ]; then eval "$(ssh-agent)"; fi
ssh-add -D >/dev/null
ssh-add "${DEPLOY_SSH_FILE}"

echo "-------------------------------"
echo "        Configure SSH          "
echo "-------------------------------"
echo
echo "DEPLOY SSH FILE         : ${DEPLOY_SSH_FILE}"
echo "DEPLOY SSH FINGERPRINT  : ${DEPLOY_SSH_FINGERPRINT}"
echo
echo "> Next steps:"
echo "  .devtools/deploy.sh    # Deploy to remote"
echo
