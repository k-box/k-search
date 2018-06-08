#!/usr/bin/env bash
CONSOLE="bin/console"
CONFIG_FILE=".env"

rm -fr var/cache/*

${CONSOLE} cache:warmup
${CONSOLE} doctrine:migrations:migrate --no-interaction

# Fix folder permissions
chown www-data:www-data --recursive ./var

# Start Supervisor daemon
supervisord
