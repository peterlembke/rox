#!/usr/bin/env bash
# Proves that mongodb_restore_indexes() in main.sh runs the index script for a Laravel project, with the right paths, and skips other projects.
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
MAIN_SH="$SCRIPT_DIR/../main.sh"

TEST_ROOT="$(mktemp -d)"
trap 'rm -rf "$TEST_ROOT"' EXIT

# rox next to a Laravel project (ai1, has artisan) and a project without artisan (infohub)
mkdir -p "$TEST_ROOT/rox" "$TEST_ROOT/ai1" "$TEST_ROOT/infohub"
touch "$TEST_ROOT/ai1/artisan"

# Extract only mongodb_restore_indexes() from main.sh, so the test exercises the real production code.
FUNCTION_SOURCE="$(sed -n '/^mongodb_restore_indexes()/,/^}/p' "$MAIN_SH")"
if [ -z "$FUNCTION_SOURCE" ]; then
  echo "FAIL: could not extract mongodb_restore_indexes() from $MAIN_SH" 1>&2
  exit 1
fi
eval "$FUNCTION_SOURCE"

# Stubs: no containers are started; container_exec only prints what it would run.
notice() { :; }
container_exec() { echo "$*"; }
# notice() is followed by a bare echo in main.sh; empty lines are dropped before comparing

assert_equals() {
  local description="$1"
  local expected="$2"
  local actual="$3"

  if [ "$expected" != "$actual" ]; then
    echo "FAIL: $description (expected '$expected', got '$actual')" 1>&2
    exit 1
  fi
  echo "PASS: $description"
}

COMPOSE_DIR="$TEST_ROOT/rox"

ROX_BASE_DIR="/var/www/ai1"
assert_equals "a Laravel project runs the index script in appserver for the database" \
  "appserver dockerhost php /var/www/rox/script/mongodb-restore-indexes.php /var/www/ai1 aktivbo_analytics_cache_dev" \
  "$(mongodb_restore_indexes "aktivbo_analytics_cache_dev" | sed "/^$/d")"

touch "$TEST_ROOT/artisan"
ROX_BASE_DIR="/var/www"
assert_equals "the root checkout (/var/www) is found one level above rox" \
  "appserver dockerhost php /var/www/rox/script/mongodb-restore-indexes.php /var/www aktivbo_analytics_cache_dev" \
  "$(mongodb_restore_indexes "aktivbo_analytics_cache_dev" | sed "/^$/d")"
rm "$TEST_ROOT/artisan"

ROX_BASE_DIR="/var/www/infohub"
assert_equals "a project without artisan runs nothing" \
  "" \
  "$(mongodb_restore_indexes "aktivbo_analytics_cache_dev" | sed "/^$/d")"

echo "All mongodb_restore_indexes tests passed."
