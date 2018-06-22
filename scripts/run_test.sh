#!/usr/bin/env bash

set -e

export APP_ENV=test

echo "** Creating database"
bin/console doctrine:schema:drop --force --quiet
bin/console doctrine:schema:create --quiet

echo "** Running phpunit"
vendor/bin/phpunit ${@}
