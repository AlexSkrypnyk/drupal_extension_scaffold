#!/usr/bin/env bash
##
# Setup SSH.
#
# - adds the SSH key to the SSH agent
#
# It is a good practice to create a separate user with own SSH key for
# every project.

set -eu
[ -n "${DEBUG:-}" ] && set -x

# The fingerprint of the SSH key.
SSH_KEY_FINGERPRINT="${SSH_KEY_FINGERPRINT:-}"

#-------------------------------------------------------------------------------

[ -z "${SSH_KEY_FINGERPRINT}" ] && echo "ERROR: Missing required value for SSH_FINGERPRINT" && exit 1

mkdir -p "${HOME}/.ssh/"

echo -e "Host *\n\tStrictHostKeyChecking no\n" >"${HOME}/.ssh/config"

file="${SSH_KEY_FINGERPRINT//:/}"
file="${HOME}/.ssh/id_rsa_${file//\"/}"

if [ ! -f "${file:-}" ]; then
  echo "ERROR: Unable to find SSH key file ${file}."
  exit 1
fi

if [ -z "${SSH_AGENT_PID:-}" ]; then
  eval "$(ssh-agent)";
fi

ssh-add -D >/dev/null
ssh-add "${file}"

echo "-------------------------------"
echo "          Setup SSH            "
echo "-------------------------------"
echo
echo "SSH file          : ${file}"
echo "SSH fingerprint   : ${SSH_KEY_FINGERPRINT}"
echo "Loaded identities :"
ssh-add -l
