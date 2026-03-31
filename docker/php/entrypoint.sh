#!/bin/sh
set -eu

cd /app

# Checked-in cache files can pin old production config into local runs.
find forums/cache -maxdepth 1 -type f -name 'cache_*.php' -delete || true

mkdir -p forums/img/avatars

exec php -S 0.0.0.0:8080 -t /app
