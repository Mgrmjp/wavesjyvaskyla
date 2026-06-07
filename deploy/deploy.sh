#!/bin/bash
set -euo pipefail

echo "=== Deploy Waves to Hetzner ==="

REMOTE_USER="${REMOTE_USER:-root}"
REMOTE_HOST="${REMOTE_HOST:-77.42.90.123}"
REMOTE_DIR="${REMOTE_DIR:-/var/www/wavesjyvaskyla}"

echo "Target: ${REMOTE_USER}@${REMOTE_HOST}:${REMOTE_DIR}"

# 1. Deploy files (code + compiled assets only; never overwrite server state)
rsync -avz \
    --exclude='.git' \
    --exclude='.env' \
    --exclude='.env.*' \
    --exclude='node_modules' \
    --exclude='screenshots' \
    --exclude='temp' \
    --exclude='src' \
    --exclude='.agents' \
    --exclude='.codex' \
    --exclude='docker*' \
    --exclude='Dockerfile' \
    --exclude='README.md' \
    --exclude='DESIGN_REF.png' \
    --exclude='docs/' \
    --exclude='data/*.json' \
    --exclude='data/*.sqlite' \
    --exclude='data/*.db' \
    --exclude='data/ai-menu-image-batches/' \
    --exclude='uploads/' \
    ./ ${REMOTE_USER}@${REMOTE_HOST}:${REMOTE_DIR}/

# 2. Remove sensitive/dev artifacts if a previous deploy left them behind
ssh ${REMOTE_USER}@${REMOTE_HOST} "\
    rm -f ${REMOTE_DIR}/.env ${REMOTE_DIR}/.env.* && \
    rm -f ${REMOTE_DIR}/DESIGN_REF.png && \
    rm -rf ${REMOTE_DIR}/temp ${REMOTE_DIR}/data/ai-menu-image-batches \
"

# 3. Fix permissions
ssh ${REMOTE_USER}@${REMOTE_HOST} "chown -R www-data:www-data ${REMOTE_DIR} && chmod -R 755 ${REMOTE_DIR} && chmod -R 775 ${REMOTE_DIR}/data"

echo "=== Done ==="
echo "Admin: https://wavesjyvaskyla.fi/admin/"
