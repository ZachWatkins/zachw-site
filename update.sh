#!/bin/bash
# This script is used to update the application on the server.
if [ ! -f .env ]; then
    cp .env.example .env
    php artisan key:generate
fi
php artisan optimize
php artisan migrate --force
