#!/usr/bin/env sh
set -eu

cd /var/www/bus
mkdir -p storage/runtime storage/logs

exec "$@"
