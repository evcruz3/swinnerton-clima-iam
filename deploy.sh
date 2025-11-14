#!/bin/bash

# Keycloak Deployment Script
# This script handles the deployment of Keycloak with Docker Compose

set -e  # Exit on any error

echo "=========================================="
echo "Keycloak Deployment Script"
echo "=========================================="
echo ""

# Check if .env file exists
if [ ! -f .env ]; then
    echo "ERROR: .env file not found!"
    echo ""
    echo "Please create .env file from .env.example:"
    echo "  cp .env.example .env"
    echo "  nano .env"
    echo ""
    echo "Make sure to update:"
    echo "  - POSTGRES_PASSWORD (use a strong password)"
    echo "  - KEYCLOAK_ADMIN_PASSWORD (use a strong password)"
    echo "  - CERTBOT_EMAIL (your email for SSL notifications)"
    exit 1
fi

echo "[1/5] Checking prerequisites..."

# Check if Docker is installed
if ! command -v docker &> /dev/null; then
    echo "ERROR: Docker is not installed. Please run setup-server.sh first."
    exit 1
fi

# Check if Docker Compose is installed
if ! docker compose version &> /dev/null; then
    echo "ERROR: Docker Compose is not installed. Please run setup-server.sh first."
    exit 1
fi

echo "✓ Docker is installed"
echo "✓ Docker Compose is installed"

echo ""
echo "[2/5] Checking DNS configuration..."
HOSTNAME=$(grep KC_HOSTNAME .env | cut -d '=' -f2 | tr -d ' ')
if [ -z "$HOSTNAME" ]; then
    HOSTNAME="devsso.swinnertonsolutions.com"
fi

SERVER_IP=$(curl -s ifconfig.me || echo "Unable to detect")
DNS_IP=$(dig +short $HOSTNAME | tail -n1 || echo "Unable to resolve")

echo "Hostname: $HOSTNAME"
echo "Server IP: $SERVER_IP"
echo "DNS resolves to: $DNS_IP"

if [ "$SERVER_IP" != "$DNS_IP" ] && [ "$DNS_IP" != "Unable to resolve" ]; then
    echo ""
    echo "WARNING: DNS mismatch detected!"
    echo "The hostname $HOSTNAME does not resolve to this server's IP address."
    echo "SSL certificate generation will fail if DNS is not configured correctly."
    echo ""
    read -p "Do you want to continue anyway? (y/N): " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        echo "Deployment cancelled."
        exit 1
    fi
fi

echo ""
echo "[3/5] Creating required directories..."
mkdir -p nginx/conf.d

echo ""
echo "[4/5] Pulling Docker images..."
docker compose pull

echo ""
echo "[5/5] Starting services..."
docker compose up -d

echo ""
echo "Waiting for services to start..."
sleep 10

echo ""
echo "=========================================="
echo "Deployment Status"
echo "=========================================="
docker compose ps

echo ""
echo "=========================================="
echo "Deployment Complete!"
echo "=========================================="
echo ""
echo "Services are starting up. This may take a few minutes."
echo ""
echo "To monitor the logs:"
echo "  docker compose logs -f"
echo ""
echo "To check service status:"
echo "  docker compose ps"
echo ""
echo "Once SSL certificates are obtained, access Keycloak at:"
echo "  https://$HOSTNAME"
echo "  Admin Console: https://$HOSTNAME/admin"
echo ""
echo "Note: First startup may take longer as SSL certificates are generated."
echo "      Monitor nginx logs: docker compose logs -f nginx"
echo ""
