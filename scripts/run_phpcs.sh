#!/usr/bin/env bash

set -e

echo "** Running php-cs-fixer"
vendor/bin/php-cs-fixer fix --dry-run -v

echo "** Running Phpstan analysis"
vendor/bin/phpstan analyze --configuration .phpstan.neon --level 7 --no-progress src/
