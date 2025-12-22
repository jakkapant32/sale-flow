#!/bin/bash
# Build script for Render.com (optional)
echo "No build steps required for PHP application"
echo "Installing composer dependencies (if any)..."
composer install --no-dev --optimize-autoloader || echo "Composer not needed"

