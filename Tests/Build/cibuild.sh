#!/usr/bin/env bash

echo "PWD: $(pwd)"

.Build/bin/phpunit --colors -c Tests/Build/UnitTests.xml
