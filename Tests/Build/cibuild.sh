#!/usr/bin/env bash

cd ..
echo "cd to $(pwd)"

./bin/phpunit --colors -c typo3conf/ext/tika/Tests/Build/UnitTests.xml