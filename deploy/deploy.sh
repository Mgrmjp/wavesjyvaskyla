#!/bin/bash
set -euo pipefail

echo "=== Deploy Waves to Hetzner ==="

REMOTE_USER="${REMOTE_USER:-root}"
REMOTE_HOST="${REMOTE_HOST:-77.42.90.123}"
REMOTE_DIR="${REMOTE_DIR:-/var/www/wavesjyvaskyla}"

echo "Target: ${REMOTE_USER}@${REMOTE_HOST}:${REMOTE_DIR}"

# 1. Deploy files
rsync -avz --exclude='.git' --exclude='docker*' --exclude='Dockerfile' --exclude='README.md' \
    ./ ${REMOTE_USER}@${REMOTE_HOST}:${REMOTE_DIR}/

# 2. Fix permissions
ssh ${REMOTE_USER}@${REMOTE_HOST} "chown -R www-data:www-data ${REMOTE_DIR} && chmod -R 755 ${REMOTE_DIR} && chmod -R 775 ${REMOTE_DIR}/data"

echo "=== Done ==="
echo "Admin: https://wavesjyvaskyla.fi/admin/"
