#!/usr/bin/env bash
# Proves that workspace_detect() in main.sh points ROX_BASE_DIR at the
# ai1/ai2/ai3 subfolder the shell is actually in, not at the root checkout.
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
MAIN_SH="$SCRIPT_DIR/../main.sh"

TEST_ROOT="$(mktemp -d)"
trap 'rm -rf "$TEST_ROOT"' EXIT

mkdir -p "$TEST_ROOT/rox" "$TEST_ROOT/ai1" "$TEST_ROOT/ai2" "$TEST_ROOT/ai3"

# Extract only the workspace_detect() function from main.sh, so the test
# exercises the real production code instead of a re-implementation.
FUNCTION_SOURCE="$(sed -n '/^workspace_detect() {/,/^}/p' "$MAIN_SH")"
if [ -z "$FUNCTION_SOURCE" ]; then
  echo "FAIL: could not extract workspace_detect() from $MAIN_SH" 1>&2
  exit 1
fi
eval "$FUNCTION_SOURCE"

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

run_case() {
  local workspaceDir="$1"
  local expectedWorkspaceName="$2"
  local expectedBaseDir="$3"
  local result

  result="$(
    COMPOSE_DIR="$TEST_ROOT/rox"
    ROX_WORKSPACE_NAME=""
    ROX_BASE_DIR="/var/www"
    cd "$workspaceDir"
    workspace_detect
    echo "$ROX_WORKSPACE_NAME|$ROX_BASE_DIR"
  )"

  assert_equals "$workspaceDir workspace name" "$expectedWorkspaceName" "${result%%|*}"
  assert_equals "$workspaceDir base dir" "$expectedBaseDir" "${result##*|}"
}

run_case "$TEST_ROOT" "" "/var/www"
run_case "$TEST_ROOT/ai1" "ai1" "/var/www/ai1"
run_case "$TEST_ROOT/ai2" "ai2" "/var/www/ai2"
run_case "$TEST_ROOT/ai3" "ai3" "/var/www/ai3"

echo "All workspace_detect tests passed."
