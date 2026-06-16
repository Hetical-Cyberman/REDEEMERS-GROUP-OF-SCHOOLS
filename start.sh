#!/usr/bin/env sh
set -e

: "${PORT:=3000}"

export DB_HOST="${DB_HOST:-127.0.0.1}"
export DB_NAME="${DB_NAME:-school_event_access}"
export DB_USER="${DB_USER:-root}"
export DB_PASS="${DB_PASS:-}"
export DB_PORT="${DB_PORT:-3306}"
export SCHOOL_NAME="${SCHOOL_NAME:-School Event Check-In}"
export STAFF_USERNAME="${STAFF_USERNAME:-admin}"
export STAFF_PASSWORD="${STAFF_PASSWORD:-change-this-password}"

php -S 0.0.0.0:$PORT -t .
