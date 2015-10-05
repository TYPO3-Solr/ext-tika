#!/usr/bin/env bash

echo "PWD: $(pwd)"

./vendor/bin/phpunit --colors -c Tests/Build/UnitTests.xml
