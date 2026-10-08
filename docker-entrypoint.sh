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

# Discover packages and ensure clean configuration
php artisan package:discover --ansi || true
php artisan config:clear || true


# Generate symlink for public uploads
php artisan storage:link || true

# Ensure database directory and SQLite file exist with proper permissions as fallback
mkdir -p /var/www/html/database
if [ ! -f /var/www/html/database/database.sqlite ]; then
    touch /var/www/html/database/database.sqlite
fi
chown -R www-data:www-data /var/www/html/database
chmod -R 775 /var/www/html/database
chmod 664 /var/www/html/database/database.sqlite

# If DATABASE_URL is provided, configure PostgreSQL into .env and Apache
if [ -n "$DATABASE_URL" ]; then
    export DB_CONNECTION=pgsql
    sed -i 's/^DB_CONNECTION=.*/DB_CONNECTION=pgsql/' /var/www/html/.env 2>/dev/null || true
    if ! grep -q "^DATABASE_URL=" /var/www/html/.env; then
        echo "DATABASE_URL=\"$DATABASE_URL\"" >> /var/www/html/.env
    fi
    echo "PostgreSQL DATABASE_URL detected. Configured in .env and environment."
fi

# Ensure .env is readable by Apache
chmod 644 /var/www/html/.env 2>/dev/null || true

# Pass environment variables to Apache web server workers
echo "PassEnv DATABASE_URL DB_CONNECTION APP_KEY APP_ENV APP_DEBUG" > /etc/apache2/conf-available/docker-env.conf
a2enconf docker-env 2>/dev/null || true

# Run database migrations and seeding
echo "Running database migrations for ${DB_CONNECTION:-sqlite}..."
php artisan migrate --force --no-interaction || true
echo "Running initial database seeder for admin and initial data..."
php artisan db:seed --force --no-interaction || true


# Execute main Apache foreground process
echo "Starting Apache on port ${PORT}..."
exec apache2-foreground

