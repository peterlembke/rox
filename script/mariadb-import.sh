#!/bin/bash

usage() {
  echo "Usage: $(basename "$0") /full/path/to/backup-folder" >&2
  echo "- Run as a user with permission to manage the MariaDB service." >&2
  echo "- The script will stop the MariaDB service, restore the data directory, then start the service again." >&2
  echo "- The path must be a directory created by 'mariadb-backup --backup' (and usually '--prepare')." >&2
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
need_cmd mariadb-backup
need_cmd chown
need_cmd service

DATA_DIR="/var/lib/mysql"

echo "Preparing to restore MariaDB data directory from: $RESTORE_DIR"

# Step 1/5: Always stop the service
echo "Step 1/5: Stopping MariaDB service..."
service mariadb stop
if [[ $? -ne 0 ]]; then
  echo "Error: Failed to stop MariaDB service." >&2
  exit 1
fi
echo "Step 1/5: OK - MariaDB service stopped."

# Step 2/5: Clean data directory - check before and after
echo "Step 2/5: Cleaning data directory '$DATA_DIR'..."

IS_DIR_EMPTY=$(find "$DATA_DIR" -mindepth 1 -maxdepth 1 2>/dev/null | head -1)
if [[ -n "$IS_DIR_EMPTY" ]]; then
  rm -rf "$DATA_DIR"/*
  if [[ $? -ne 0 ]]; then
    echo "Error: Failed to clean data directory '$DATA_DIR'." >&2
    exit 1
  fi
fi

IS_DIR_EMPTY=$(find "$DATA_DIR" -mindepth 1 -maxdepth 1 2>/dev/null | head -1)
if [[ -n "$IS_DIR_EMPTY" ]]; then
  echo "Error: Data directory '$DATA_DIR' is not empty after cleaning." >&2
  exit 1
fi
echo "Step 2/5: OK - Data directory '$DATA_DIR' is empty."

# Step 3/5: Move back backup files and verify
echo "Step 3/5: Moving back backup files into data directory..."
mariadb-backup --move-back --target-dir="$RESTORE_DIR"
if [[ $? -ne 0 ]]; then
  echo "Error: mariadb-backup --move-back failed." >&2
  exit 1
fi

IS_DIR_EMPTY=$(find "$DATA_DIR" -mindepth 1 -maxdepth 1 2>/dev/null | head -1)
if [[ -z "$IS_DIR_EMPTY" ]]; then
  echo "Error: Data directory '$DATA_DIR' is still empty after move-back. Database was not imported." >&2
  exit 1
fi
echo "Step 3/5: OK - Backup files moved into '$DATA_DIR'."

# Step 4/5: Fix ownership
echo "Step 4/5: Fixing ownership for '$DATA_DIR'..."
chown -R mysql:mysql "$DATA_DIR"
if [[ $? -ne 0 ]]; then
  echo "Error: Failed to fix ownership for '$DATA_DIR'." >&2
  exit 1
fi
echo "Step 4/5: OK - Ownership fixed."

# Step 5/5: Always start the service
echo "Step 5/5: Starting MariaDB service..."
service mariadb start
if [[ $? -ne 0 ]]; then
  echo "Error: Failed to start MariaDB service." >&2
  exit 1
fi
echo "Step 5/5: OK - MariaDB service started."

echo "Success: MariaDB restore completed from '$RESTORE_DIR'."
echo "MariaDB service has been restarted."