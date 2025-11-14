#!/bin/bash

# Keycloak Server Setup Script for Ubuntu
# This script installs all prerequisites needed for the Keycloak Docker Compose deployment

set -e  # Exit on any error

echo "=========================================="
echo "Keycloak Server Setup Script"
echo "=========================================="
echo ""

# Check if running as root
if [ "$EUID" -ne 0 ]; then
    echo "ERROR: Please run this script as root or with sudo"
    exit 1
fi

echo "[1/7] Updating system packages..."
apt-get update
apt-get upgrade -y

echo ""
echo "[2/7] Installing required dependencies..."
apt-get install -y \
    apt-transport-https \
    ca-certificates \
    curl \
    gnupg \
    lsb-release \
    git \
    ufw

echo ""
echo "[3/7] Installing Docker..."
# Remove old Docker versions if they exist
apt-get remove -y docker docker-engine docker.io containerd runc 2>/dev/null || true

# Add Docker's official GPG key
install -m 0755 -d /etc/apt/keyrings
curl -fsSL https://download.docker.com/linux/ubuntu/gpg | gpg --dearmor -o /etc/apt/keyrings/docker.gpg
chmod a+r /etc/apt/keyrings/docker.gpg

# Add Docker repository
echo \
  "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.gpg] https://download.docker.com/linux/ubuntu \
  $(lsb_release -cs) stable" | tee /etc/apt/sources.list.d/docker.list > /dev/null

# Install Docker Engine
apt-get update
apt-get install -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin

echo ""
echo "[4/7] Configuring Docker..."
# Enable and start Docker service
systemctl enable docker
systemctl start docker

# Add current user to docker group (if not root)
if [ -n "$SUDO_USER" ]; then
    usermod -aG docker $SUDO_USER
    echo "Added user '$SUDO_USER' to docker group"
fi

echo ""
echo "[5/7] Configuring firewall..."
# Enable UFW if not already enabled
if ! ufw status | grep -q "Status: active"; then
    echo "y" | ufw enable
fi

# Allow SSH (important!)
ufw allow ssh

# Allow HTTP and HTTPS
ufw allow 80/tcp
ufw allow 443/tcp

# Reload firewall
ufw reload

echo ""
echo "[6/7] Verifying installations..."
docker --version
docker compose version
git --version

echo ""
echo "[7/7] Testing Docker..."
docker run --rm hello-world

echo ""
echo "=========================================="
echo "Setup Complete!"
echo "=========================================="
echo ""
echo "Installed components:"
echo "  - Docker Engine: $(docker --version)"
echo "  - Docker Compose: $(docker compose version)"
echo "  - Git: $(git --version)"
echo ""
echo "Firewall rules:"
echo "  - SSH (22): Allowed"
echo "  - HTTP (80): Allowed"
echo "  - HTTPS (443): Allowed"
echo ""
echo "Next steps:"
echo "  1. Log out and log back in (or run 'newgrp docker') to use Docker without sudo"
echo "  2. Clone the repository: git clone https://github.com/evcruz3/swinnerton-clima-iam.git"
echo "  3. Navigate to the directory: cd swinnerton-clima-iam"
echo "  4. Copy .env.example to .env and configure your settings"
echo "  5. Run the deployment: docker compose up -d"
echo ""
echo "IMPORTANT: Make sure DNS for devsso.swinnertonsolutions.com points to this server's IP!"
echo ""
