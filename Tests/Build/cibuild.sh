#!/usr/bin/env bash

#cd ..
#echo "cd to $(pwd)"
echo "in $(pwd)"

./vendor/bin/phpunit --colors -c Tests/Build/UnitTests.xml
