#!/usr/bin/env bash

set -euo pipefail

# This wrapper runs PHP inside the Docker Compose `php` service for editor validation.
# It proxies common php CLI commands used by the validator/language server.

PROJECT_ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$PROJECT_ROOT"

# Choose docker compose command (v2 `docker compose` vs v1 `docker-compose`).
if command -v docker >/dev/null 2>&1 && docker compose version >/dev/null 2>&1; then
  DOCKER_COMPOSE=(docker compose)
elif command -v docker-compose >/dev/null 2>&1; then
  DOCKER_COMPOSE=(docker-compose)
else
  echo "Docker Compose is not installed (need docker compose or docker-compose)" >&2
  exit 1
fi

# Compose project name derived from directory to keep a stable namespace.
PROJECT_NAME="$(basename "$PROJECT_ROOT" | tr '[:upper:]' '[:lower:]')"
SERVICE="php"

# Ensure compose context is valid
if ! "${DOCKER_COMPOSE[@]}" -p "$PROJECT_NAME" ps >/dev/null 2>&1; then
  echo "No compose project found at $PROJECT_ROOT" >&2
  exit 1
fi

# Start php service if not running
if ! "${DOCKER_COMPOSE[@]}" -p "$PROJECT_NAME" ps --status running --services | grep -q "^${SERVICE}$"; then
  "${DOCKER_COMPOSE[@]}" -p "$PROJECT_NAME" up -d ${SERVICE}
fi

exec "${DOCKER_COMPOSE[@]}" -p "$PROJECT_NAME" exec -T ${SERVICE} php "$@"


