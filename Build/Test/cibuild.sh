#!/usr/bin/env bash

echo "PWD: $(pwd)"

test -n "$TIKA_PATH" || export TIKA_PATH="$HOME/bin"

export TYPO3_PATH_WEB=$(pwd)/.Build/Web

UNIT_BOOTSTRAP=".Build/vendor/typo3/testing-framework/Resources/Core/Build/UnitTestsBootstrap.php"

.Build/bin/phpunit --colors -c Build/Test/UnitTests.xml --bootstrap=$UNIT_BOOTSTRAP
