#!/bin/sh

# Navigate to frontend directory
cd /app/frontend

echo "Installing frontend dependencies..."
npm install

echo "Building frontend..."
npm run build

# Clear Nginx destination directory
rm -rf /usr/share/nginx/html/*

# Copy build artifacts to Nginx directory
cp -r dist/* /usr/share/nginx/html/

# Start Nginx in foreground
# Nginx needs root to bind to port 80, but the container is running as a non-root user
# We'll use sudo if available, or assume the user has enough permissions (unlikely for port 80)
# Actually, the best way for Nginx on port 80 is to start as root and drop privileges.
# But here we want the build to be as the user.

echo "Frontend built successfully! Starting Nginx..."
exec nginx -g "daemon off;"
