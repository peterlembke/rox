#!/bin/bash

# Database credentials - change these if needed
DB_USER="root"
DB_PASS="topsecret"

usage() {
  echo "Usage: $(basename "$0") /full/path/to/file.sql [--force]" >&2
  echo "- Run as a user with permission to access MariaDB." >&2
  echo "- The script will import the SQL file into a database derived from the file name." >&2
  echo "- Example: 'laravelapi_dev.sql' will import into database 'laravelapi_dev'." >&2
  echo "- If the database already has tables, import is skipped unless --force is used." >&2
}

if [[ ${1-} == "-h" || ${1-} == "--help" ]]; then
  usage
  exit 0
fi

FORCE=false
for arg in "$@"; do
  if [[ "$arg" == "--force" ]]; then
    FORCE=true
  fi
done

# Remove --force from positional parameters
ARGS=()
for arg in "$@"; do
  if [[ "$arg" != "--force" ]]; then
    ARGS+=("$arg")
  fi
done
set -- "${ARGS[@]+${ARGS[@]}}"

if [[ $# -lt 1 ]]; then
  echo "Error: Missing required argument: path to SQL file" >&2
  usage
  exit 1
fi

SQL_FILE="$1"

# Verify file exists
if [[ ! -f "$SQL_FILE" ]]; then
  echo "Error: File does not exist: $SQL_FILE" >&2
  exit 1
fi

# Check required commands
need_cmd() { command -v "$1" >/dev/null 2>&1 || { echo "Error: '$1' not found in PATH" >&2; exit 1; }; }
need_cmd mariadb

# Derive database name from file name by removing .sql extension
BASE_NAME=$(basename "$SQL_FILE")
DB_NAME="${BASE_NAME%.sql}"

if [[ "$DB_NAME" == "$BASE_NAME" ]]; then
  echo "Error: File does not have a .sql extension: $SQL_FILE" >&2
  exit 1
fi

echo "Preparing to import '$SQL_FILE' into database '$DB_NAME'."

# Check if the database already has tables
TABLE_COUNT_EXISTING=$(mariadb -u"$DB_USER" -p"$DB_PASS" -e "SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA='$DB_NAME';" --skip-column-names 2>/dev/null)
if [[ -n "$TABLE_COUNT_EXISTING" && "$TABLE_COUNT_EXISTING" -gt 0 ]]; then
  if [[ "$FORCE" == "false" ]]; then
    echo -e "\e[1;33mDatabase '$DB_NAME' already has $TABLE_COUNT_EXISTING tables. Skipping import. Use --force to overwrite.\e[0m"
    exit 0
  else
    echo "Database '$DB_NAME' already has $TABLE_COUNT_EXISTING tables. --force used, proceeding with import."
  fi
fi

# Step 1/3: Drop the database if it exists
echo "Step 1/3: Dropping database '$DB_NAME' if it exists..."
mariadb -u"$DB_USER" -p"$DB_PASS" -e "DROP DATABASE IF EXISTS \`$DB_NAME\`;"
if [[ $? -ne 0 ]]; then
  echo "Error: Failed to drop database '$DB_NAME'." >&2
  exit 1
fi
# Verify the database was actually dropped
DB_EXISTS=$(mariadb -u"$DB_USER" -p"$DB_PASS" -e "SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME='$DB_NAME';" --skip-column-names 2>/dev/null)
if [[ -n "$DB_EXISTS" ]]; then
  echo "Error: Database '$DB_NAME' still exists after drop." >&2
  exit 1
fi
echo "Step 1/3: OK - Database '$DB_NAME' dropped."

# Step 2/3: Create the database
echo "Step 2/3: Creating database '$DB_NAME'..."
mariadb -u"$DB_USER" -p"$DB_PASS" -e "CREATE DATABASE \`$DB_NAME\`;"
if [[ $? -ne 0 ]]; then
  echo "Error: Failed to create database '$DB_NAME'." >&2
  exit 1
fi
# Verify the database was actually created
DB_EXISTS=$(mariadb -u"$DB_USER" -p"$DB_PASS" -e "SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME='$DB_NAME';" --skip-column-names 2>/dev/null)
if [[ -z "$DB_EXISTS" ]]; then
  echo "Error: Database '$DB_NAME' does not exist after creation." >&2
  exit 1
fi
echo "Step 2/3: OK - Database '$DB_NAME' created."

# Step 3/3: Disable foreign key checks, import the SQL file, re-enable foreign key checks
echo "Step 3/3: Importing '$SQL_FILE' into database '$DB_NAME'..."
SQL_FILE_ABS=$(realpath "$SQL_FILE")

# Create a cleaned temporary copy to fix MySQL-to-MariaDB compatibility issues
CLEAN_FILE=$(mktemp)
sed 's/\\-/-/g' "$SQL_FILE_ABS" | sed '/NOTE_VERBOSITY/d' > "$CLEAN_FILE"

mariadb -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "
SET GLOBAL FOREIGN_KEY_CHECKS=0;
SOURCE $CLEAN_FILE;
SET GLOBAL FOREIGN_KEY_CHECKS=1;
"
IMPORT_EXIT=$?
rm -f "$CLEAN_FILE"

if [[ $IMPORT_EXIT -ne 0 ]]; then
  echo "Error: Failed to import '$SQL_FILE' into database '$DB_NAME'." >&2
  mariadb -u"$DB_USER" -p"$DB_PASS" -e "SET GLOBAL FOREIGN_KEY_CHECKS=1;" 2>/dev/null
  exit 1
fi
# Verify that tables were actually imported
TABLE_COUNT=$(mariadb -u"$DB_USER" -p"$DB_PASS" -e "SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA='$DB_NAME';" --skip-column-names 2>/dev/null)
if [[ -z "$TABLE_COUNT" || "$TABLE_COUNT" -eq 0 ]]; then
  echo "Error: No tables found in database '$DB_NAME' after import." >&2
  exit 1
fi
echo "Step 3/3: OK - SQL file imported. $TABLE_COUNT tables found in database '$DB_NAME'. Foreign key checks re-enabled."

echo "Success: Database '$DB_NAME' imported from '$SQL_FILE'."