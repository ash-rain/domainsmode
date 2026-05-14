#!/bin/sh
set -e

SSL_DIR=/etc/nginx/ssl
CERT="$SSL_DIR/cert.pem"
KEY="$SSL_DIR/key.pem"

if [ ! -f "$CERT" ] || [ ! -f "$KEY" ]; then
    echo "[nginx] Generating self-signed SSL certificate..."
    mkdir -p "$SSL_DIR"
    openssl req -x509 \
        -newkey rsa:4096 \
        -keyout "$KEY" \
        -out "$CERT" \
        -days 365 \
        -nodes \
        -subj "/CN=localhost/O=DomainsMode/C=US" \
        -addext "subjectAltName=DNS:localhost,IP:127.0.0.1"
    chmod 600 "$KEY"
    echo "[nginx] Certificate generated — valid 365 days."
else
    echo "[nginx] SSL certificate already exists, skipping generation."
fi

# Remove default nginx config and start
rm -f /etc/nginx/conf.d/default.conf
exec nginx -g 'daemon off;'
