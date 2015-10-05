#!/usr/bin/env bash

#cd ..
#echo "cd to $(pwd)"
echo "in $(pwd)"

./bin/phpunit --colors -c typo3conf/ext/tika/Tests/Build/UnitTests.xml
