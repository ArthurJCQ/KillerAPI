#!/bin/sh
# usage:
# bin/functional_tests.sh [phpunit args...]
#
# example:
# bin/functional_tests.sh tests/Path/To/Tests/
# or:
# bin/functional_tests.sh --filter=ClassTest

export APP_ENV="test"

## Clear cache
rm -rf var/cache/*

# run tests
php vendor/bin/codecept run Api $@
