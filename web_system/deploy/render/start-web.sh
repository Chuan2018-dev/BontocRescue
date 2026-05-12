#!/usr/bin/env sh
set -eu

export PORT="${PORT:-10000}"

envsubst '${PORT}' < /etc/nginx/templates/default.conf.template > /etc/nginx/http.d/default.conf

mkdir -p /run/nginx /var/lib/nginx/tmp/client_body /var/log/nginx

php artisan storage:link || true
php artisan migrate --force
php artisan config:clear
php artisan config:cache

php-fpm -D
php artisan reverb:start --host=127.0.0.1 --port=8080 &

exec nginx -g 'daemon off;'
