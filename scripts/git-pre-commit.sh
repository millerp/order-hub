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
        echo "Checking $SERVICE..."
        if [ -f "$SERVICE/vendor/bin/pint" ]; then
            # Run Pint on the files relative to the service root
            # Using a temporary file to store the list of files to avoid argument length limits
            # and to handle them correctly within the service context
            cd "$SERVICE" || continue
            
            # Pint accepts a space-separated list of files
            ./vendor/bin/pint $SERVICE_FILES
            
            # Go back to project root
            cd ..
            
            # Re-stage modified files
            for FILE in $SERVICE_FILES; do
                git add "$SERVICE/$FILE"
            done
            FILES_MODIFIED=1
        else
            echo "Warning: Pint not found in $SERVICE/vendor/bin/pint. Skipping."
        fi
    fi
done

if [ $FILES_MODIFIED -eq 1 ]; then
    echo "Laravel Pint has finished. Modified files (if any) have been re-staged."
else
    echo "Laravel Pint found no issues or no files in supported services were staged."
fi

exit 0
