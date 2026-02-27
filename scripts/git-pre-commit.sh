#!/bin/sh

# Laravel Pint Pre-commit Hook
# Runs Pint on staged PHP files grouped by service

echo "Running Laravel Pint on staged PHP files..."

# Get staged PHP files
STAGED_FILES=$(git diff --cached --name-only --diff-filter=ACM | grep '\.php$')

if [ -z "$STAGED_FILES" ]; then
    echo "No staged PHP files found. Skipping Pint."
    exit 0
fi

# Services with Pint
SERVICES="auth-service order-service payment-service product-service user-service notification-service"

# Track if any files were changed by Pint
FILES_MODIFIED=0

for SERVICE in $SERVICES; do
    # Filter staged files belonging to this service
    SERVICE_FILES=$(echo "$STAGED_FILES" | grep "^$SERVICE/" | sed "s|^$SERVICE/||")

    if [ ! -z "$SERVICE_FILES" ]; then
        echo "Checking $SERVICE in container..."
        
        # Run Pint inside a temporary container for the service
        # Using 'docker compose run --rm' to destroy the container after execution
        # We pass the list of files to Pint
        docker compose run --rm "$SERVICE" ./vendor/bin/pint $SERVICE_FILES
        
        # Re-stage modified files
        for FILE in $SERVICE_FILES; do
            git add "$SERVICE/$FILE"
        done
        FILES_MODIFIED=1
    fi
done

if [ $FILES_MODIFIED -eq 1 ]; then
    echo "Laravel Pint has finished. Modified files (if any) have been re-staged."
else
    echo "Laravel Pint found no issues or no files in supported services were staged."
fi

exit 0
