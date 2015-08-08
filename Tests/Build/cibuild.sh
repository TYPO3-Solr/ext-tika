#!/usr/bin/env bash

set -x

pwd
ls -l
ls -l ..

cd ..
./bin/phpunit --colors -c typo3conf/ext/tika/Tests/Build/UnitTests.xml