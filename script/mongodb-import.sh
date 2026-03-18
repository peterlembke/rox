#!/usr/bin/env bash

set -euo pipefail

usage() {
  echo "Usage: $(basename "$0") /full/path/to/database/folder" >&2
  echo "- The last segment of the path is treated as the MongoDB database name." >&2
}

if [[ ${1-} == "-h" || ${1-} == "--help" ]]; then
  usage
  exit 0
fi

if [[ $# -lt 1 ]]; then
  echo "Error: Missing required argument: path to database folder" >&2
  usage
  exit 1
fi

IMPORT_DIR="$1"

# Verify folder exists
if [[ ! -d "$IMPORT_DIR" ]]; then
  echo "Error: Folder does not exist: $IMPORT_DIR" >&2
  exit 1
fi

# Extract database name from last subfolder
DB_NAME="$(basename "$IMPORT_DIR")"
if [[ -z "$DB_NAME" ]]; then
  echo "Error: Could not determine database name from path: $IMPORT_DIR" >&2
  exit 1
fi

# Check required commands
if ! command -v mongorestore >/dev/null 2>&1; then
  echo "Error: 'mongorestore' command not found. Please install MongoDB Database Tools." >&2
  exit 1
fi

if ! command -v mongosh >/dev/null 2>&1; then
  echo "Error: 'mongosh' command not found. Please install MongoDB Shell." >&2
  exit 1
fi

URI="mongodb://root:infohub@localhost:27017/$DB_NAME"
AUTH_DB="admin"
AUTH_MECH="SCRAM-SHA-256"

echo "Preparing to import database '$DB_NAME' from: $IMPORT_DIR"
echo "Checking MongoDB connection parameters: $URI (auth DB: $AUTH_DB, mech: $AUTH_MECH)"

echo "Step 1/2: Dropping existing database '$DB_NAME' (if any)..."
if mongosh admin -u root -p infohub --quiet --eval "db.getSiblingDB('$DB_NAME').dropDatabase()" >/dev/null; then
  echo "- Database '$DB_NAME' dropped (or did not exist)."
else
  echo "Error: Failed to drop database '$DB_NAME'." >&2
  exit 1
fi

echo "Step 2/2: Importing dump from folder..."
set +e
mongorestore \
  --uri="$URI" \
  --authenticationDatabase "$AUTH_DB" \
  --authenticationMechanism "$AUTH_MECH" \
  --drop \
  --preserveUUID \
  "$IMPORT_DIR"
RESTORE_EXIT_CODE=$?
set -e

if [[ $RESTORE_EXIT_CODE -eq 0 ]]; then
  echo "Success: Import completed for database '$DB_NAME'."
  exit 0
else
  echo "Error: Import failed for database '$DB_NAME' (exit code: $RESTORE_EXIT_CODE)." >&2
  exit $RESTORE_EXIT_CODE
fi