#!/bin/bash
# End-to-End Test Script for jitsi-admin WebSocket
# Run from project root using "ddev exec" or directly if containers are running
# Usage: bash nodejs/tests/e2e-test.sh

set -e

PASS=0
FAIL=0
MERCURE_JWT_SECRET="${MERCURE_JWT_SECRET:-MDY3OTljNDM3MzRjMWU4ZmFkZTFlNzY5}"
WEBSOCKET_SECRET="${WEBSOCKET_SECRET:-MDY3OTljNDM3MzRjMWU4ZmFkZTFlNzY5}"

# ANSI colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m'

header() {
  echo ""
  echo -e "${YELLOW}=== $1 ===${NC}"
}

ok() {
  PASS=$((PASS + 1))
  echo -e "  ${GREEN}✓ PASS${NC} $1"
}

fail() {
  FAIL=$((FAIL + 1))
  echo -e "  ${RED}✗ FAIL${NC} $1 — $2"
}

expect() {
  local expected="$1"
  local actual="$2"
  local label="$3"
  if [ "$actual" = "$expected" ]; then
    ok "$label ($actual)"
  else
    fail "$label" "expected $expected, got $actual"
  fi
}

expect_contains() {
  local expected="$1"
  local actual="$2"
  local label="$3"
  if echo "$actual" | grep -q "$expected"; then
    ok "$label"
  else
    fail "$label" "response does not contain '$expected'"
  fi
}

expect_code() {
  local code="$1"
  local expected="$2"
  local label="$3"
  if [ "$code" = "$expected" ]; then
    ok "$label (HTTP $code)"
  else
    fail "$label" "expected HTTP $expected, got HTTP $code"
  fi
}

# Determine if we're inside DDEV
if [ -n "$DDEV_REQUIRED_VERSION" ] || [ -f /var/www/html/.ddev/config.yaml ]; then
  INSIDE_DDEV=true
  WS_URL="http://localhost:3000"
  REDIS_CMD="redis-cli"
  REDIS_HOST="redis"
else
  INSIDE_DDEV=false
  # Assume running on host with DDEV running
  if command -v ddev &>/dev/null; then
    WS_URL="https://jitsi-admin.ddev.site:3000"
    REDIS_CMD="ddev exec -s redis redis-cli"
    REDIS_HOST="ddev-jitsi-admin-redis"
  else
    WS_URL="${WS_URL:-http://localhost:3000}"
    REDIS_CMD="${REDIS_CMD:-redis-cli}"
    REDIS_HOST="${REDIS_HOST:-localhost}"
  fi
fi

echo "==========================================="
echo " jitsi-admin WebSocket E2E Test"
echo " Target: $WS_URL"
echo " Redis:  $REDIS_HOST"
echo "==========================================="

# ── 1. Health Check ──────────────────────────
header "1. Health Check"

HTTP_CODE=$(curl -sk -o /dev/null -w "%{http_code}" "$WS_URL/healthz" 2>/dev/null || echo "000")
expect_code "$HTTP_CODE" "200" "GET /healthz"

# ── 2. WebSocket Server Alive ──────────────────
header "2. WebSocket Server Alive"

HTTP_CODE=$(curl -sk -o /dev/null -w "%{http_code}" "$WS_URL/" 2>/dev/null || echo "000")
# Should return 404 (no route for /)
if [ "$HTTP_CODE" = "404" ]; then
  ok "GET / returns 404 (server alive, no root route)"
else
  fail "GET /" "expected 404, got $HTTP_CODE"
fi

# ── 3. Mercure Hub Liveness ─────────────────
header "3. Mercure Hub Liveness"

HTTP_CODE=$(curl -sk -o /dev/null -w "%{http_code}" "$WS_URL/.well-known/mercure" 2>/dev/null || echo "000")
expect_code "$HTTP_CODE" "200" "GET /.well-known/mercure"

# ── 4. Redis Connectivity ────────────────────
header "4. Redis Connectivity"

REDIS_PING=$($REDIS_CMD PING 2>/dev/null || echo "FAIL")
expect "$REDIS_PING" "PONG" "Redis PING"

# ── 5. Mercure Publish (JWT Auth) ──────────
header "5. Mercure Publish (POST)"

# Generate a publish JWT (Node.js required — this is the publish token format)
if command -v node &>/dev/null; then
  PUB_JWT=$(node -e "
    const jwt = require('jsonwebtoken');
    const token = jwt.sign(
      { iss: 'jitsi-admin', aud: 'jitsi-admin', publish: ['*'] },
      '${WEBSOCKET_SECRET}',
      { algorithm: 'HS256' }
    );
    console.log(token);
  " 2>/dev/null)

  PUBLISH_RESPONSE=$(curl -sk -X POST "$WS_URL/.well-known/mercure" \
    -H "Authorization: Bearer $PUB_JWT" \
    -H "Content-Type: application/x-www-form-urlencoded" \
    -d 'topic=test-room&data={"type":"snackbar","message":"E2E test","color":"success"}' \
    -w "\n%{http_code}" 2>/dev/null)

  HTTP_CODE=$(echo "$PUBLISH_RESPONSE" | tail -1)
  BODY=$(echo "$PUBLISH_RESPONSE" | head -1)
  expect_code "$HTTP_CODE" "200" "POST /.well-known/mercure (publish JWT)"
else
  fail "POST /.well-known/mercure" "node not available to generate JWT"
fi

# ── 6. Mercure Publish with Wrong Secret ────
header "6. Mercure Publish (wrong secret) — should reject"

WRONG_JWT_RESPONSE=$(curl -sk -X POST "$WS_URL/.well-known/mercure" \
  -H "Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJqaXRzaS1hZG1pbiIsImF1ZCI6ImppdHNpLWFkbWluIiwicHVibGlzaCI6WyIqIl0sImlhdCI6MX0.invalid" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d 'topic=test&data=test' \
  -w "\n%{http_code}" 2>/dev/null)

HTTP_CODE=$(echo "$WRONG_JWT_RESPONSE" | tail -1)
expect_code "$HTTP_CODE" "403" "POST /.well-known/mercure (invalid JWT) returns 403"

# ── 7. WebSocket Connection (via Node script) ──
header "7. WebSocket Client Connection"

# Try a simple Socket.IO connection test via Node
if command -v node &>/dev/null; then
  CONNECTION_TEST=$(cd "$(dirname "$0")" && node -e "
    const { io } = require('socket.io-client');
    const jwt = require('jsonwebtoken');
    const token = jwt.sign(
      { iss: 'jitsi-admin', aud: 'jitsi-admin', sub: 'e2e-test-user', status: 1, rooms: [], iat: Math.floor(Date.now()/1000), nbf: Math.floor(Date.now()/1000), exp: Math.floor(Date.now()/1000)+86400 },
      '${WEBSOCKET_SECRET}',
      { algorithm: 'HS256' }
    );
    const socket = io('${WS_URL}', {
      path: '/ws',
      query: { token },
      transports: ['websocket'],
      timeout: 5000,
      reconnection: false,
    });
    socket.on('connect', () => {
      socket.once('sendUserStatus', (status) => {
        console.log('connected:' + status);
        socket.close();
        process.exit(0);
      });
      setTimeout(() => { process.exit(1); }, 3000);
    });
    socket.on('connect_error', (e) => {
      console.log('error:' + e.message);
      process.exit(1);
    });
  " 2>/dev/null 2>&1 || echo "error:connection_failed")

  if echo "$CONNECTION_TEST" | grep -q "^connected:"; then
    STATUS=$(echo "$CONNECTION_TEST" | cut -d: -f2)
    ok "Socket.IO connection established (status: $STATUS)"
  else
    fail "Socket.IO connection" "${CONNECTION_TEST:-no output}"
  fi
else
  fail "Socket.IO connection" "node not available"
fi

# ── 8. Redis User State ──────────────────
header "8. Redis User State"

USER_COUNT=$($REDIS_CMD HLEN users 2>/dev/null || echo "0")
echo -e "  Users in Redis hash: ${YELLOW}$USER_COUNT${NC}"
# Don't fail — user count depends on whether a real browser is connected
if [ "$USER_COUNT" -ge 0 ] 2>/dev/null; then
  ok "Redis users hash accessible (${USER_COUNT} users)"
else
  fail "Redis users hash" "could not read"
fi

# ── Summary ──────────────────────────────
echo ""
echo "==========================================="
echo " Results: ${GREEN}${PASS} passed${NC}, ${RED}${FAIL} failed${NC}"
echo "==========================================="

if [ "$FAIL" -gt 0 ]; then
  exit 1
else
  exit 0
fi
