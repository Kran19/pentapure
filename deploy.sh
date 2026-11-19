#!/bin/bash
set -e

echo "Deploying application..."

# 1. Pull the latest code from your git repository
echo "Pulling latest code..."
git pull origin main

# 2. Install PHP dependencies
echo "Installing composer dependencies..."
composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev --no-scripts

# 3. Install Node dependencies and build assets (optional but recommended if using Vite/Mix)
# Uncomment these if you are building assets on the server instead of committing them
# echo "Building Node assets..."
# npm install
# npm run build

# 4. Clear and cache Laravel configurations
echo "Caching configuration, routes, and views..."
php artisan view:clear
php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 5. Run database migrations (will not prompt for confirmation because of --force)
echo "Running migrations..."
php artisan migrate --force

# 6. Set correct permissions for storage and cache directories (adjust www-data if needed)
# echo "Setting permissions..."
# chown -R www-data:www-data storage bootstrap/cache
# chmod -R 775 storage bootstrap/cache

# 7. Preserve and sync uploaded LR images across storage, vault, and public_html before/after asset sync
echo "Preserving and syncing LR images..."
mkdir -p storage/app/public/lr_images storage/app/lr_vault public/lr_images ../public_html/lr_images
rsync -au ../public_html/lr_images/ storage/app/public/lr_images/ 2>/dev/null || true
rsync -au public/lr_images/ storage/app/public/lr_images/ 2>/dev/null || true
rsync -au storage/app/lr_vault/ storage/app/public/lr_images/ 2>/dev/null || true
rsync -au storage/app/public/lr_images/ storage/app/lr_vault/ 2>/dev/null || true
rsync -au storage/app/public/lr_images/ public/lr_images/ 2>/dev/null || true

# 8. Sync public assets to public_html
echo "Syncing public assets to live web directory..."
rsync -av --exclude='index.php' --exclude='.htaccess' public/ ../public_html/
rsync -au storage/app/public/lr_images/ ../public_html/lr_images/ 2>/dev/null || true

echo "Application deployed successfully!"
