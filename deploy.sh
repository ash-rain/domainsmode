#!/bin/bash
# First-time VPS setup for DomainsMode.
# Run once: ssh user@vps 'bash -s' < deploy.sh
set -e

APP_DIR="${HOME}/domainsmode"
REPO_URL="${1:?Usage: deploy.sh <git-repo-url>}"

echo "=== DomainsMode — First-time deploy ==="

# Clone repo if not present
if [ ! -d "$APP_DIR" ]; then
    echo "[1/5] Cloning repository..."
    git clone "$REPO_URL" "$APP_DIR"
else
    echo "[1/5] Repository exists, pulling latest..."
    cd "$APP_DIR" && git pull origin main
fi

cd "$APP_DIR"

# Check for .env
if [ ! -f .env ]; then
    cp .env.prod.example .env
    echo ""
    echo "============================================"
    echo "  .env created from template."
    echo "  Edit $APP_DIR/.env with real"
    echo "  secrets, then run this script again."
    echo "============================================"
    exit 1
fi

# Source .env for variable access
set -a; source .env; set +a

echo "[2/5] Starting services..."
docker compose -f docker-compose.prod.yml up -d --build

echo "[3/5] Waiting for nginx to be ready..."
sleep 5

echo "[4/5] Obtaining Let's Encrypt certificate..."
docker compose -f docker-compose.prod.yml run --rm certbot certonly \
    --webroot -w /var/www/certbot \
    --email "$CERTBOT_EMAIL" \
    --agree-tos --no-eff-email \
    -d "$DOMAIN"

echo "[5/5] Reloading nginx with real certificate..."
docker compose -f docker-compose.prod.yml exec nginx nginx -s reload

echo ""
echo "=== Deploy complete ==="
echo "  UI:    https://$DOMAIN"
echo "  API 1: https://$DOMAIN:8001"
echo "  API 2: https://$DOMAIN:8002"
