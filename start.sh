#!/usr/bin/env sh
set -e

if [ ! -f config.php ]; then
  cp config.sample.php config.php
fi

: "${PORT:=8000}"
php -S 0.0.0.0:$PORT -t .