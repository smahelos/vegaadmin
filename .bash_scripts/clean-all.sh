#!/bin/bash

# Clear Cache and Build Laravel 12 Application
# Based on clean-all.prompt.md

echo "🚀 Starting Laravel cache clear and rebuild process..."

# Navigate to config directory
echo "📁 Navigating to config directory..."
cd /_Data/Dockers/Production/Invoice/config/

# Clear caches
echo "🧹 Clearing basset cache..."
docker exec INVOICE-php-fpm php artisan basset:clear

echo "🧹 Clearing optimization cache..."
docker exec INVOICE-php-fpm php artisan optimize:clear

# Restart Docker containers
echo "🐳 Stopping Docker containers..."
docker compose down

echo "🐳 Starting Docker containers..."
docker compose up -d

# Navigate to application directory
echo "📁 Navigating to application directory..."
cd /_Data/Dockers/Production/Invoice/data/www/html/

# Set permissions
echo "🔑 Setting permissions for storage..."
sudo chmod -R 777 storage

echo "🔑 Setting permissions for bootstrap/cache..."
sudo chmod -R 777 bootstrap/cache

# Build frontend assets
echo "🏗️ Building frontend assets..."
npm run build

# Navigate back to config directory
echo "📁 Navigating back to config directory..."
cd /_Data/Dockers/Production/Invoice/config/

# Cache views and routes
echo "🗃️ Caching views..."
docker exec INVOICE-php-fpm php artisan view:cache

echo "🗃️ Caching routes..."
docker exec INVOICE-php-fpm php artisan route:cache

echo "✅ Laravel cache clear and rebuild completed successfully!"
