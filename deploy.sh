#!/bin/bash
set -e

echo "Deploying application..."

# 1. Pull the latest code from your git repository
echo "Pulling latest code..."
git pull origin main

# 2. Install PHP dependencies
echo "Installing composer dependencies..."
composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev

# 3. Install Node dependencies and build assets (optional but recommended if using Vite/Mix)
# Uncomment these if you are building assets on the server instead of committing them
# echo "Building Node assets..."
# npm install
# npm run build

# 4. Clear and cache Laravel configurations
echo "Caching configuration..."
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

echo "Application deployed successfully!"
