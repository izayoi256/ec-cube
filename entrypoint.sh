#!/bin/sh

set -e

if [ -n "${HOST_UID}" -a "${HOST_UID}" != "$(id -u www-data)" ]; then
    usermod -u "${HOST_UID}" www-data
fi

exec docker-php-entrypoint "$@"
