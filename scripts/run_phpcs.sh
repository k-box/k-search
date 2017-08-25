#!/usr/bin/env bash

set -e

vendor/bin/php-cs-fixer fix --dry-run

# PhpStan has been disable, re-enable as soon as we remove the old code from the Repository
#vendor/bin/phpstan analyze --level 1 src/ tests/
