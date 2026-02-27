#!/bin/sh

# Script to install the git pre-commit hook

HOOK_FILE=".git/hooks/pre-commit"
SOURCE_FILE="scripts/git-pre-commit.sh"

if [ ! -d ".git" ]; then
    echo "Error: .git directory not found. Please run this script from the project root."
    exit 1
fi

if [ ! -f "$SOURCE_FILE" ]; then
    echo "Error: $SOURCE_FILE not found."
    exit 1
fi

echo "Installing Git pre-commit hook..."

# Copy or symlink the script to .git/hooks/pre-commit
# Symlinking is better if you want changes to scripts/git-pre-commit.sh to reflect immediately
# but might have issues on Windows. Since this is likely WSL or Linux, let's try copying first
# or symlinking if the OS supports it.

if cp "$SOURCE_FILE" "$HOOK_FILE"; then
    chmod +x "$HOOK_FILE"
    chmod +x "$SOURCE_FILE"
    echo "Successfully installed pre-commit hook at $HOOK_FILE"
else
    echo "Error: Failed to install hook."
    exit 1
fi

exit 0
