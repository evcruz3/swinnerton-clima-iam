#!/bin/bash

# CodeIgniter + Keycloak Setup Script
# This script helps you quickly set up the development environment

set -e

echo "=========================================="
echo "CodeIgniter + Keycloak Setup"
echo "=========================================="
echo ""

# Check if composer is installed
if ! command -v composer &> /dev/null; then
    echo "ERROR: Composer is not installed."
    echo "Please install composer from https://getcomposer.org/"
    exit 1
fi

# Check PHP version
PHP_VERSION=$(php -r "echo PHP_VERSION;" | cut -d. -f1,2)
PHP_MIN_VERSION="8.1"

if [ "$(printf '%s\n' "$PHP_MIN_VERSION" "$PHP_VERSION" | sort -V | head -n1)" != "$PHP_MIN_VERSION" ]; then
    echo "ERROR: PHP 8.1 or higher is required. Current version: $PHP_VERSION"
    exit 1
fi

echo "✓ Composer found: $(composer --version)"
echo "✓ PHP version: $PHP_VERSION"
echo ""

# Install dependencies
echo "[1/4] Installing dependencies..."
composer install

echo ""
echo "[2/4] Setting up environment file..."

if [ ! -f .env ]; then
    if [ -f .env.example ]; then
        cp .env.example .env
        echo "✓ Created .env from .env.example"
    elif [ -f vendor/codeigniter4/framework/app/.env ]; then
        cp vendor/codeigniter4/framework/app/.env .env
        echo "✓ Created .env from CodeIgniter framework"
    else
        echo "WARNING: Could not find .env.example or framework .env"
        echo "Please create .env manually"
    fi
else
    echo "✓ .env already exists"
fi

echo ""
echo "[3/4] Creating writable directories..."

mkdir -p writable/cache writable/logs writable/session writable/uploads
chmod -R 777 writable/
echo "✓ Created writable directories with proper permissions"

echo ""
echo "[4/4] Verifying configuration..."

# Check if Keycloak config exists
if [ -f app/Config/Keycloak.php ]; then
    echo "✓ Keycloak configuration found"
else
    echo "WARNING: Keycloak configuration not found at app/Config/Keycloak.php"
fi

echo ""
echo "=========================================="
echo "Setup Complete!"
echo "=========================================="
echo ""
echo "Next steps:"
echo ""
echo "1. Review Keycloak configuration:"
echo "   Edit app/Config/Keycloak.php if needed"
echo ""
echo "2. Configure Keycloak client:"
echo "   See KEYCLOAK_SETUP.md for detailed instructions"
echo ""
echo "3. Start the development server:"
echo "   php spark serve"
echo ""
echo "4. Visit the application:"
echo "   http://localhost:8080"
echo ""
echo "For detailed setup instructions, see:"
echo "  - README.md (Application setup)"
echo "  - KEYCLOAK_SETUP.md (Keycloak configuration)"
echo ""
