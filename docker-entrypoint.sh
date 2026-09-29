#!/bin/sh
set -e

# Ensure storage directory structure exists
mkdir -p /var/www/html/storage/app/public /var/www/html/storage/framework/cache /var/www/html/storage/framework/sessions /var/www/html/storage/framework/views /var/www/html/storage/logs

# Fix permissions
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Generate symlink for public uploads
php artisan storage:link || true

# Run database migrations and seeding for PostgreSQL
if [ -n "$DATABASE_URL" ] || [ "$DB_CONNECTION" = "pgsql" ]; then
    echo "Running database migrations..."
    php artisan migrate --force --no-interaction || true
    echo "Running initial seeder for admin and default data..."
    php artisan db:seed --force --no-interaction || true
fi

# Execute main Apache foreground process
exec apache2-foreground
