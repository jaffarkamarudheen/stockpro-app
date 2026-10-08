#!/bin/sh
set -e

# Configure Apache to listen on Render's assigned $PORT (Render sets PORT=10000 by default)
PORT="${PORT:-80}"
echo "Configuring Apache to listen on port ${PORT}..."
sed -i "s/Listen 80/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:80>/<VirtualHost \*:${PORT}>/" /etc/apache2/sites-available/*.conf /etc/apache2/sites-enabled/*.conf 2>/dev/null || true

# Ensure storage directory structure exists
mkdir -p /var/www/html/storage/app/public /var/www/html/storage/framework/cache /var/www/html/storage/framework/sessions /var/www/html/storage/framework/views /var/www/html/storage/logs

# Fix permissions
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# If .env does not exist, create a basic one
if [ ! -f /var/www/html/.env ]; then
    cp /var/www/html/.env.example /var/www/html/.env 2>/dev/null || touch /var/www/html/.env
fi

# Ensure APP_KEY exists
if [ -z "$APP_KEY" ]; then
    echo "Generating application encryption key..."
    php artisan key:generate --force --no-interaction || true
fi

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
echo "Starting Apache on port ${PORT}..."
exec apache2-foreground

