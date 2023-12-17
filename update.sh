#!/bin/bash
# This script is used to update the application on the server.
php artisan optimize
php artisan migrate --force
