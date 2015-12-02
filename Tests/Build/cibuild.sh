#!/usr/bin/env bash

echo "PWD: $(pwd)"

test -n "$TIKA_PATH" || export TIKA_PATH="$HOME/bin"

export TYPO3_PATH_WEB=$(pwd)/.Build/Web
.Build/bin/phpunit --colors -c Tests/Build/UnitTests.xml
