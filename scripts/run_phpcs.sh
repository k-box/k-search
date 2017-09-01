#!/usr/bin/env bash

set -e

echo "** Running php-cs-fixer"
vendor/bin/php-cs-fixer fix --dry-run

# PhpStan has been disable, re-enable as soon as we remove the old code from the Repository
#echo "** Running Phpstan analysis"
#vendor/bin/phpstan analyze --level 1 src/ tests/
