#!/usr/bin/env bash

set -x

pwd
ls -l

./bin/phpunit --colors -c ../typo3conf/ext/tika/Tests/Build/UnitTests.xml