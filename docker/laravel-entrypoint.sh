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

# Check if kafka config needs to be published
if [ ! -f "config/kafka.php" ]; then
    echo "Publishing Kafka configuration..."
    php artisan vendor:publish --provider="Junges\Kafka\LaravelKafkaServiceProvider"
    
    # Update broker list to use environment variable
    sed -i "s/'brokers' => env('KAFKA_BROKERS', 'localhost:9092'),/'brokers' => env('KAFKA_BROKERS', 'kafka:9092'),/g" config/kafka.php
fi

# Use OCTANE_SERVER from environment or default to roadrunner
OCTANE_SERVER=${OCTANE_SERVER:-frankenphp}

echo "Starting Laravel Octane with $OCTANE_SERVER..."
# Ensure RoadRunner binary is executable if using roadrunner
if [ "$OCTANE_SERVER" = "roadrunner" ] && [ -f "rr" ]; then chmod +x rr; fi

exec php artisan octane:start --server="$OCTANE_SERVER" --host=0.0.0.0 --port=8000
