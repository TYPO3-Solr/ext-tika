#!/usr/bin/env bash

echo "PWD: $(pwd)"

test -n "$TIKA_PATH" || export TIKA_PATH="$HOME/bin"

export TYPO3_PATH_WEB=$(pwd)/.Build/Web

UNIT_BOOTSTRAP=".Build/vendor/nimut/testing-framework/res/Configuration/UnitTestsBootstrap.php"
.Build/bin/phpunit --colors -c Build/Test/UnitTests.xml --bootstrap=$UNIT_BOOTSTRAP  --coverage-clover=coverage.unit.clover

if [ $? -ne "0" ]; then
    echo "Error during running the unit tests please check and fix them"
    exit 1
fi

#
# Map the travis and shell variable names to the expected
# casing of the TYPO3 core.
#
if [ -n $TYPO3_DATABASE_NAME ]; then
	export typo3DatabaseName=$TYPO3_DATABASE_NAME
else
	echo "No environment variable TYPO3_DATABASE_NAME set. Please set it to run the integration tests."
	exit 1
fi

if [ -n $TYPO3_DATABASE_HOST ]; then
	export typo3DatabaseHost=$TYPO3_DATABASE_HOST
else
	echo "No environment variable TYPO3_DATABASE_HOST set. Please set it to run the integration tests."
	exit 1
fi

if [ -n $TYPO3_DATABASE_USERNAME ]; then
	export typo3DatabaseUsername=$TYPO3_DATABASE_USERNAME
else
	echo "No environment variable TYPO3_DATABASE_USERNAME set. Please set it to run the integration tests."
	exit 1
fi

if [ -n $TYPO3_DATABASE_PASSWORD ]; then
	export typo3DatabasePassword=$TYPO3_DATABASE_PASSWORD
else
	echo "No environment variable TYPO3_DATABASE_PASSWORD set. Please set it to run the integration tests."
	exit 1
fi

INTEGRATION_BOOTSTRAP=".Build/vendor/nimut/testing-framework/res/Configuration/FunctionalTestsBootstrap.php"
.Build/bin/phpunit --colors -c Build/Test/IntegrationTests.xml --bootstrap=$INTEGRATION_BOOTSTRAP --coverage-clover=coverage.integration.clover
