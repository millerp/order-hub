#!/bin/sh

# Navigate to application directory
cd /app

# Debug information
echo "Current user: $(id)"
echo "Current directory: $(pwd)"
echo "Git version: $(git --version 2>/dev/null || echo 'Git not found')"
echo "Zip version: $(zip -v 2>/dev/null | head -n 1 || echo 'Zip not found')"
echo "PHP Zip extension: $(php -m | grep -i zip || echo 'Zip extension not found')"

if [ ! -f "composer.json" ]; then
    echo "Error: composer.json not found in /app. Please check your volume mounts."
    exit 1
fi

echo "Installing PHP dependencies (this may take a few minutes)..."
composer install --no-interaction --ignore-platform-reqs

echo "Running database migrations..."
php artisan migrate --no-interaction

# Initialize Telescope if requested and not yet installed
echo "Installing Laravel Telescope..."
php artisan telescope:install
# Re-run migrations to include telescope tables
php artisan migrate --no-interaction

echo "Starting Laravel Octane with RoadRunner..."
# Ensure RoadRunner binary is executable in case it was just installed/copied
if [ -f "rr" ]; then chmod +x rr; fi

exec php artisan octane:start --server=roadrunner --host=0.0.0.0 --port=8000
