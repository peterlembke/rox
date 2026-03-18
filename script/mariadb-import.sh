#!/usr/bin/env bash

set -euo pipefail

usage() {
  echo "Usage: $(basename "$0") /full/path/to/backup-folder" >&2
  echo "- The provided path must be a directory created by 'mariadb-backup --backup' (and usually --prepare)." >&2
}

if [[ ${1-} == "-h" || ${1-} == "--help" ]]; then
  usage
  exit 0
fi

if [[ $# -lt 1 ]]; then
  echo "Error: Missing required argument: path to backup folder" >&2
  usage
  exit 1
fi

RESTORE_DIR="$1"

# Verify folder exists
if [[ ! -d "$RESTORE_DIR" ]]; then
  echo "Error: Folder does not exist: $RESTORE_DIR" >&2
  exit 1
fi

# Check required commands
need_cmd() { command -v "$1" >/dev/null 2>&1 || { echo "Error: '$1' not found in PATH" >&2; exit 1; }; }
need_cmd sudo
need_cmd service
need_cmd mariadb-backup

# Ensure sudo won't prompt for password (non-interactive environment)
if ! sudo -n true 2>/dev/null; then
  echo "Error: This script requires passwordless sudo (sudo -n). Configure sudoers for the current user." >&2
  exit 1
fi

echo "Preparing to restore MariaDB data directory from: $RESTORE_DIR"

echo "Step 1/5: Stopping MariaDB service..."
sudo -n service mariadb stop

echo "Step 2/5: Cleaning data directory '/var/lib/mysql'..."
# Use a subshell with bash -c to let the shell expand the wildcard under sudo
sudo -n bash -c "rm -rf /var/lib/mysql/*"

echo "Step 3/5: Moving back backup files into data directory..."
sudo -n mariadb-backup --move-back --target-dir="$RESTORE_DIR"

echo "Step 4/5: Fixing ownership for '/var/lib/mysql'..."
sudo -n chown -R mysql:mysql /var/lib/mysql

echo "Step 5/5: Starting MariaDB service..."
sudo -n service mariadb start

echo "Success: MariaDB restore completed from '$RESTORE_DIR'."
