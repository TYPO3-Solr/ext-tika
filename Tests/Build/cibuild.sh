#!/usr/bin/env bash

echo "PWD: $(pwd)"

export TYPO3_PATH_WEB=$(pwd)/.Build/Web
.Build/bin/phpunit --colors -c Tests/Build/UnitTests.xml
