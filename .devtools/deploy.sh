#!/usr/bin/env bash
##
# Deploy code to a remote repository.
#
# - configures local git
# - force-pushes code to a remote code repository branch
#
# Add the following variables through CI provider UI.
# - DEPLOY_USER_NAME - name of the user who will be committing to a remote repository.
# - DEPLOY_USER_EMAIL - email address of the user who will be committing to a remote repository.
# - DEPLOY_REMOTE - remote repository to push code to.
# - DEPLOY_PROCEED - set to 1 if the deployment should proceed. Useful for testing CI configuration before an actual code push.

set -eu
[ -n "${DEBUG:-}" ] && set -x

#-------------------------------------------------------------------------------
# Variables (passed from environment; provided for reference only).
#-------------------------------------------------------------------------------

# Name of the user who will be committing to a remote repository.
DEPLOY_USER_NAME="${DEPLOY_USER_NAME:-}"

# Email address of the user who will be committing to a remote repository.
DEPLOY_USER_EMAIL="${DEPLOY_USER_EMAIL:-}"

# Remote repository to push code to.
DEPLOY_REMOTE="${DEPLOY_REMOTE:-}"

# Git branch to deploy. If not provided - current branch will be used.
DEPLOY_BRANCH="${DEPLOY_BRANCH:-}"

# Set to 1 if the deployment should proceed. Useful for testing CI configuration
# before an actual code push.
DEPLOY_PROCEED="${DEPLOY_PROCEED:-0}"

# The fingerprint of the SSH key.
DEPLOY_SSH_KEY_FINGERPRINT="${DEPLOY_SSH_KEY_FINGERPRINT:-}"

#-------------------------------------------------------------------------------

if [ -n "${DEPLOY_SSH_KEY_FINGERPRINT}" ]; then
  echo "-------------------------------"
  echo "          Setup SSH            "
  echo "-------------------------------"

  mkdir -p "${HOME}/.ssh/"
  echo -e "\nHost *\n\tStrictHostKeyChecking no\n\tUserKnownHostsFile /dev/null\n" >"${HOME}/.ssh/config"

  # Find the MD5 hash if the SSH_KEY_FINGERPRINT starts with SHA256.
  if [ "${DEPLOY_SSH_KEY_FINGERPRINT#SHA256:}" != "${DEPLOY_SSH_KEY_FINGERPRINT}" ]; then
    for file in "${HOME}"/.ssh/id_rsa*; do
      calculated_sha256_fingerprint=$(ssh-keygen -l -E sha256 -f "${file}" | awk '{print $2}')
      if [ "${calculated_sha256_fingerprint}" = "${DEPLOY_SSH_KEY_FINGERPRINT}" ]; then
        DEPLOY_SSH_KEY_FINGERPRINT=$(ssh-keygen -l -E md5 -f "${file}" | awk '{print $2}')
        DEPLOY_SSH_KEY_FINGERPRINT="${DEPLOY_SSH_KEY_FINGERPRINT#MD5:}"
        break
      fi
    done
  fi

  file="${DEPLOY_SSH_KEY_FINGERPRINT//:/}"
  file="${HOME}/.ssh/id_rsa_${file//\"/}"

  if [ ! -f "${file:-}" ]; then
    echo "ERROR: Unable to find SSH key file ${file}."
    exit 1
  fi

  if [ -z "${SSH_AGENT_PID:-}" ]; then
    eval "$(ssh-agent)"
  fi

  ssh-add -D
  ssh-add "${file}"
  ssh-add -l
fi

echo "-------------------------------"
echo "          Deploy code          "
echo "-------------------------------"

[ -z "${DEPLOY_USER_NAME}" ] && echo "ERROR: Missing required value for DEPLOY_USER_NAME" && exit 1
[ -z "${DEPLOY_USER_EMAIL}" ] && echo "ERROR: Missing required value for DEPLOY_USER_EMAIL" && exit 1
[ -z "${DEPLOY_REMOTE}" ] && echo "ERROR: Missing required value for DEPLOY_REMOTE" && exit 1

[ "${DEPLOY_PROCEED}" != "1" ] && echo "> Skip deployment because $DEPLOY_PROCEED is not set to 1" && exit 0

echo "> Configure git user name and email, but only if not already set."
[ "$(git config --global user.name)" == "" ] && echo "> Configure global git user name ${DEPLOY_USER_NAME}." && git config --global user.name "${DEPLOY_USER_NAME}"
[ "$(git config --global user.email)" == "" ] && echo "> Configure global git user email ${DEPLOY_USER_EMAIL}." && git config --global user.email "${DEPLOY_USER_EMAIL}"

echo "> Set git to push to a matching remote branch."
git config --global push.default matching

echo "> Add remote ${DEPLOY_REMOTE}."
git remote add deployremote "${DEPLOY_REMOTE}"

if [ -z "${DEPLOY_BRANCH}" ]; then
  DEPLOY_BRANCH="$(git symbolic-ref --short HEAD)"
fi

echo "> Push code to branch ${DEPLOY_BRANCH}."
git push --force deployremote HEAD:"${DEPLOY_BRANCH}"

echo "> Push tags."
git push --force --tags deployremote || true

echo "-------------------------------"
echo "        Deployed code          "
echo "-------------------------------"
echo
echo "Remote URL    : ${DEPLOY_REMOTE}"
echo "Remote branch : ${DEPLOY_BRANCH}"
echo
echo "> Next steps:"
echo "  Navigate to Drupal.org and check that the code was successfully pushed."
echo
