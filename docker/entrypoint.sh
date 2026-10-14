#!/bin/sh
set -e

# Render passes the PORT environment variable (e.g. 10000)
PORT="${PORT:-80}"
echo "==> Configuring Nginx to listen on port ${PORT}..."
sed -i "s/LISTEN_PORT/${PORT}/g" /etc/nginx/http.d/default.conf

# Ensure storage subdirectories exist
mkdir -p /var/www/html/storage/framework/cache/data \
         /var/www/html/storage/framework/sessions \
         /var/www/html/storage/framework/views \
         /var/www/html/storage/logs \
         /var/www/html/storage/app/public \
         /var/www/html/bootstrap/cache

# Ensure correct file permissions
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Create public storage symlink if it doesn't already exist
echo "==> Creating storage link..."
php artisan storage:link --force || true

# If SQLite is selected, ensure the database file and directory exist with write permissions
DB_CONN="${DB_CONNECTION:-sqlite}"
if [ "$DB_CONN" = "sqlite" ]; then
    DB_FILE="${DB_DATABASE:-/var/www/html/database/database.sqlite}"
    DB_DIR="$(dirname "$DB_FILE")"
    mkdir -p "$DB_DIR"
    if [ ! -f "$DB_FILE" ]; then
        echo "==> SQLite database file not found. Creating $DB_FILE..."
        touch "$DB_FILE"
    fi
    chown www-data:www-data "$DB_FILE"
    chmod 664 "$DB_FILE"
    chown -R www-data:www-data "$DB_DIR"
    chmod 775 "$DB_DIR"
fi

# Run migrations if RUN_MIGRATIONS is true
if [ "${RUN_MIGRATIONS:-false}" = "true" ] || [ "${RUN_MIGRATIONS:-0}" = "1" ]; then
    echo "==> Running database migrations..."
    php artisan migrate --force
fi

# Cache config, routes, and views for production performance
if [ "${APP_ENV:-production}" = "production" ] && [ "${DISABLE_CACHE:-false}" != "true" ]; then
    echo "==> Caching Laravel configuration, routes, and views..."
    php artisan config:cache || true
    php artisan route:cache || true
    php artisan view:cache || true
fi

echo "==> Starting web application via Supervisor..."
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
