#!/usr/bin/env bash
##
# Deploy code to a remote repository.
#
# - configures local git
# - adds deployment SSH key to SSH agent
# - force-pushes code to a remote code repository branch
#
# It is a good practice to create a separate Deployer user with own SSH key for
# every project.
#
# Add the following variables through CircleCI UI.
# DEPLOY_USER_NAME - name of the user who will be committing to a remote repository.
# DEPLOY_USER_EMAIL - email address of the user who will be committing to a remote repository.
# DEPLOY_REMOTE - remote repository to push code to.
# DEPLOY_PROCEED - set to 1 if the deployment should proceed. Useful for testing CI configuration before an actual code push.
#
# Other variables:
# DEPLOY_BRANCH - git branch to deploy.
# DEPLOY_SSH_FINGERPRINT - the fingerprint of the SSH key of the user on behalf of which the deployment is performed.

set -e

DEPLOY_USER_NAME="${DEPLOY_USER_NAME}"
DEPLOY_USER_EMAIL="${DEPLOY_USER_EMAIL}"
DEPLOY_REMOTE="${DEPLOY_REMOTE:-}"
DEPLOY_BRANCH="${DEPLOY_BRANCH:-}"
DEPLOY_SSH_FINGERPRINT="${DEPLOY_SSH_FINGERPRINT:-}"
DEPLOY_PROCEED="${DEPLOY_PROCEED:-0}"

[ -z "${DEPLOY_USER_NAME}" ] && echo "ERROR: Missing required value for DEPLOY_USER_NAME" && exit 1
[ -z "${DEPLOY_USER_EMAIL}" ] && echo "ERROR: Missing required value for DEPLOY_USER_EMAIL" && exit 1
[ -z "${DEPLOY_REMOTE}" ] && echo "ERROR: Missing required value for DEPLOY_REMOTE" && exit 1
[ -z "${DEPLOY_SSH_FINGERPRINT}" ] && echo "ERROR: Missing required value for DEPLOY_SSH_FINGERPRINT" && exit 1

[ "${DEPLOY_PROCEED}" != "1" ] && echo "==> Skipping deployment because \$DEPLOY_PROCEED is not set to 1" && exit 0

# Configure git and SSH to connect to remote servers for deployment.
mkdir -p "${HOME}/.ssh/"
echo -e "Host *\n\tStrictHostKeyChecking no\n" > "${HOME}/.ssh/config"
DEPLOY_SSH_FILE="${DEPLOY_SSH_FINGERPRINT//:}"
DEPLOY_SSH_FILE="${HOME}/.ssh/id_rsa_${DEPLOY_SSH_FILE//\"}"
[ ! -f "${DEPLOY_SSH_FILE}" ] && echo "ERROR: Unable to find Deploy SSH key file ${DEPLOY_SSH_FILE}" && exit 1
if [ -z "${SSH_AGENT_PID}" ]; then eval "$(ssh-agent)"; fi
ssh-add -D > /dev/null
ssh-add "${DEPLOY_SSH_FILE}"

[ "$(git config --global user.name)" == "" ] && echo "==> Configuring global git user name" && git config --global user.name "${DEPLOY_USER_NAME}"
[ "$(git config --global user.email)" == "" ] && echo "==> Configuring global git user email" && git config --global user.email "${DEPLOY_USER_EMAIL}"

git config --global push.default matching

echo "==> Adding remote ${DEPLOY_REMOTE}"
git remote add deployremote "${DEPLOY_REMOTE}"

echo "==> Deploying to remote ${DEPLOY_REMOTE}"
# shellcheck disable=SC2086
git push --force --tags deployremote ${DEPLOY_BRANCH}
