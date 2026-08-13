#!/bin/bash
set -e

APP_DIR=~/www/roywenman.co.uk/apps/barcode-scan
PUBLIC_DIR=~/www/roywenman.co.uk/public_html/projects/barcode-scan

cd "$APP_DIR"

composer install --no-dev --optimize-autoloader --no-interaction
php artisan migrate --force
php artisan storage:link

rm -rf "${PUBLIC_DIR:?}"/*
cp -r public/. "$PUBLIC_DIR/"
sed -i "s|__DIR__\\.'/\\.\\./|__DIR__.'/../../../apps/barcode-scan/|g" "$PUBLIC_DIR/index.php"

echo "Deploy complete"
