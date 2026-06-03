#!/usr/bin/env bash
# Copy Vite build into Laravel public/ (keeps index.php for admin + API).
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
DIST="$ROOT/transport-frontend/dist"
PUBLIC="$ROOT/public"

if [[ ! -d "$DIST" ]]; then
  echo "Run first: cd transport-frontend && npm ci && npm run build"
  exit 1
fi

if [[ ! -f "$PUBLIC/index.php" ]]; then
  echo "Missing $PUBLIC/index.php — wrong directory?"
  exit 1
fi

cp -r "$DIST"/* "$PUBLIC/"
echo "Merged $DIST -> $PUBLIC (index.php preserved)"
