#!/usr/bin/env bash
CONSOLE="bin/console"

rm -fr var/cache/*

${CONSOLE} cache:warmup

# Fix folder permissions
chown www-data:www-data --recursive ./var

# Start Supervisor daemon
supervisord
