#!/bin/sh
# Health check script for PHP-FPM

set -e

# Check if PHP-FPM is running and responding
SCRIPT_NAME=/ping \
SCRIPT_FILENAME=/ping \
REQUEST_METHOD=GET \
cgi-fcgi -bind -connect 127.0.0.1:9000 || exit 1

# Check if Laravel is responding (if app is fully booted)
if [ -f /var/www/artisan ]; then
    # Simple check if Laravel can boot
    php /var/www/artisan --version > /dev/null 2>&1 || exit 1
fi

exit 0
