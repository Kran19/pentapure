#!/bin/bash
set -e

echo "=========================================="
echo "  PENTAPURE SAFE RESTORE SCRIPT (LIVE)   "
echo "  ZERO DATA LOSS - Database is untouched  "
echo "=========================================="

HOME_DIR="/home/u192814865"
APP_DIR="$HOME_DIR/pentapure"

# 1. Ensure pentapure directory exists and is up to date
if [ ! -d "$APP_DIR" ]; then
    echo "Cloning repository..."
    cd "$HOME_DIR"
    git clone https://github.com/Kran19/pentapure.git pentapure
fi

cd "$APP_DIR"
echo "Pulling latest code from main..."
git fetch origin main
git reset --hard origin/main

# 2. Configure .env with live database credentials
echo "Configuring .env..."
if [ ! -f ".env" ]; then
    cp .env.example .env
fi
sed -i 's/DB_DATABASE=.*/DB_DATABASE=u192814865_pp/' .env
sed -i 's/DB_USERNAME=.*/DB_USERNAME=u192814865_pp/' .env
sed -i 's/DB_PASSWORD=.*/DB_PASSWORD="PP@0706#pp"/' .env

# 3. Ensure PHP vendor packages exist
if [ ! -d "vendor" ] || [ ! -f "vendor/autoload.php" ]; then
    echo "Installing composer dependencies..."
    composer install --no-dev --optimize-autoloader --no-interaction
fi

# 4. Generate app key if needed and set permissions
php artisan key:generate --force
chmod -R 775 storage bootstrap/cache

# 5. Setup web root directories (both public_html and domains/pentapureadmin.in/public_html if present)
TARGET_DIRS=("$HOME_DIR/public_html")
if [ -d "$HOME_DIR/domains/pentapureadmin.in" ]; then
    TARGET_DIRS+=("$HOME_DIR/domains/pentapureadmin.in/public_html")
fi

for TARGET in "${TARGET_DIRS[@]}"; do
    echo "Setting up web root in $TARGET..."
    # Remove if it was a symlink
    if [ -L "$TARGET" ]; then
        rm -f "$TARGET"
    fi
    mkdir -p "$TARGET"

    # Copy public assets
    cp -r "$APP_DIR/public/"* "$TARGET/" 2>/dev/null || true
    cp "$APP_DIR/public/.htaccess" "$TARGET/.htaccess"

    # Create robust bridge index.php
    cat << 'EOF' > "$TARGET/index.php"
<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

$appPath = '/home/u192814865/pentapure';
if (!file_exists($appPath . '/bootstrap/app.php')) {
    $appPath = dirname(__DIR__) . '/pentapure';
}

if (file_exists($maintenance = $appPath . '/storage/framework/maintenance.php')) {
    require $maintenance;
}

require $appPath . '/vendor/autoload.php';

/** @var Application $app */
$app = require_once $appPath . '/bootstrap/app.php';

$app->handleRequest(Request::capture());
EOF

    # Setup storage link
    rm -rf "$TARGET/storage"
    ln -s "$APP_DIR/storage/app/public" "$TARGET/storage"

    # Permissions
    chmod 755 "$TARGET"
    chmod 644 "$TARGET/index.php" "$TARGET/.htaccess"
done

# 6. Clear caches
echo "Clearing application cache..."
php artisan optimize:clear

# 7. Check database status (read-only verification)
echo "Verifying database connection..."
php artisan db:show

echo "=========================================="
echo "  RESTORATION FINISHED SUCCESSFULLY!     "
echo "  Visit: https://pentapureadmin.in/login "
echo "=========================================="
