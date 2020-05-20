#!/usr/bin/env bash
set -e

wget https://scrutinizer-ci.com/ocular.phar
php ocular.phar code-coverage:upload --format=php-clover coverage.unit.clover
php ocular.phar code-coverage:upload --format=php-clover coverage.integration.clover
rm ocular.phar