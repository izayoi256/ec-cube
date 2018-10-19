#!/bin/sh
set -e

if [ -n "${HOST_UID}" -a "${HOST_UID}" != "$(id -u www-data)" ]; then
    usermod -u "${HOST_UID}" www-data
fi

xdebug=$(cat << EOS
xdebug.remote_host=$(ip route | awk 'NR==1 {print $3}')
xdebug.remote_port=${XDEBUG_PORT}
EOS
)
echo "${xdebug}" >> /usr/local/etc/php/conf.d/my.ini

if [ -e "composer.json" ]; then
    sudo -u www-data composer install
fi

if [ -e "package.json" ]; then
    sudo -u www-data npm install
fi

if [ "${1#-}" != "$1" ]; then
	set -- apache2-foreground "$@"
fi

exec "$@"
