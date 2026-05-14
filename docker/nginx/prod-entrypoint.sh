#!/bin/sh
set -e

DOMAIN="${DOMAIN:-domainsmode.nsh.one}"
CERT_DIR="/etc/letsencrypt/live/$DOMAIN"
FALLBACK_DIR="/etc/nginx/ssl"

if [ ! -f "$CERT_DIR/fullchain.pem" ] || [ ! -f "$CERT_DIR/privkey.pem" ]; then
    echo "[nginx] No Let's Encrypt cert found — generating self-signed fallback..."
    mkdir -p "$CERT_DIR" "$FALLBACK_DIR"

    openssl req -x509 -newkey rsa:2048 -keyout "$FALLBACK_DIR/key.pem" -out "$FALLBACK_DIR/cert.pem" \
        -days 1 -nodes -subj "/CN=$DOMAIN"

    ln -sf "$FALLBACK_DIR/cert.pem" "$CERT_DIR/fullchain.pem"
    ln -sf "$FALLBACK_DIR/key.pem" "$CERT_DIR/privkey.pem"

    echo "[nginx] Self-signed fallback ready — run certbot to get real certs."
else
    echo "[nginx] Let's Encrypt certificate found."
fi

# Render templates with envsubst
for template in /etc/nginx/templates/*.template; do
    [ -f "$template" ] || continue
    outfile="/etc/nginx/conf.d/$(basename "$template" .template)"
    envsubst '${DOMAIN}' < "$template" > "$outfile"
done

rm -f /etc/nginx/conf.d/default.conf
exec nginx -g 'daemon off;'
