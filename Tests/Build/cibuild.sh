#!/usr/bin/env bash

set -x

pwd
ls -l
ls -l ..

cd ..

ls -l typo3conf
ls -l typo3conf/ext
ls -l typo3conf/ext/tika

./bin/phpunit --colors -c typo3conf/ext/tika/Tests/Build/UnitTests.xml