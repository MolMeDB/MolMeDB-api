#!/bin/sh
set -e

# Initialize storage directory if empty
# -----------------------------------------------------------
# If the storage directory is empty, copy the initial contents
# and set the correct permissions.
# -----------------------------------------------------------
# if [ ! "$(ls -A /var/www/storage)" ]; then
#   echo "Initializing storage directory..."
#   cp -R /var/www/storage-init/. /var/www/storage
#   chown -R www-data:www-data /var/www/storage
# fi

# Remove storage-init directory
rm -rf /var/www/storage-init

php artisan config:clear

# Run Laravel migrations
# -----------------------------------------------------------
# Ensure the database schema is up to date.
# -----------------------------------------------------------
php artisan migrate --force

php artisan config:clear && php artisan cache:clear && php artisan route:clear && php artisan view:clear && php artisan optimize:clear

php artisan storage:link --silent

php artisan key:generate --silent

# Clear and cache configurations
# -----------------------------------------------------------
# Improves performance by caching config and routes.
# -----------------------------------------------------------
php artisan optimize

# Run the default command
exec "$@"