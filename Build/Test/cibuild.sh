#!/usr/bin/env bash

echo "PWD: $(pwd)"

test -n "$TIKA_PATH" || export TIKA_PATH="$HOME/bin"

export TYPO3_PATH_WEB=$(pwd)/.Build/Web

UNIT_BOOTSTRAP=".Build/vendor/typo3/cms/typo3/sysext/core/Build/UnitTestsBootstrap.php"

if [[ $TYPO3_VERSION == "~8.7.0" || $TYPO3_VERSION == "dev-master" ]]; then
    UNIT_BOOTSTRAP=".Build/vendor/typo3/testing-framework/Resources/Core/Build/UnitTestsBootstrap.php"
fi

.Build/bin/phpunit --colors -c Build/Test/UnitTests.xml --bootstrap=$UNIT_BOOTSTRAP
