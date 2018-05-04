#!/usr/bin/env bash

set -e
set -x

rm -f "var/data_test.db"

export APP_ENV=test

echo "** Testing migrations"
# Database drop ins not working on SQLite
# bin/console doctrine:database:drop --force --if-exists
bin/console doctrine:database:create
bin/console doctrine:migrations:migrate --no-interaction
