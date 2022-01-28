#!/usr/bin/env bash

if [[ -n ${BASH_SOURCE[0]} ]]; then
  SCRIPT_PATH=$( cd $(dirname "${BASH_SOURCE[0]}") ; pwd -P )
else
  SCRIPT_PATH=$( cd $(dirname "$0") ; pwd -P )
fi
EXTENSION_ROOT_PATH="$SCRIPT_PATH/../../"
TIKA_SERVER_SOURCE="https://archive.apache.org/dist/tika/"

#
# Run this script once to set up a dev/test environment for this extension
# Afterwards simply running cibuild.sh will execute the tests
#

if [[ $* == *--local* ]]; then
    echo -n "Choose a TYPO3 Version (e.g. dev-main, ^11.5): "
    read -r typo3Version
    export TYPO3_VERSION=$typo3Version

    echo -n "Choose a EXT:solr Version (e.g. dev-main, dev-release-11.5.x): "
    read -r extSolrVersion
    export EXT_SOLR_VERSION=$extSolrVersion

    echo -n "Choose a tika Version (e.g. 1.24.1): "
    read -r tikaVersion
    export TIKA_VERSION=$tikaVersion

    echo -n "Choose a database hostname: "
    read -r typo3DbHost
    export TYPO3_DATABASE_HOST=$typo3DbHost

    echo -n "Choose a database name: "
    read -r typo3DbName
    export TYPO3_DATABASE_NAME=$typo3DbName

    echo -n "Choose a database user: "
    read -r typo3DbUser
    export TYPO3_DATABASE_USERNAME=$typo3DbUser

    echo -n "Choose a database password: "
    read -r typo3DbPassword
    export TYPO3_DATABASE_PASSWORD=$typo3DbPassword
fi

test -n "$TIKA_PATH" || TIKA_PATH="$HOME/bin"

if [ -z "$TIKA_VERSION" ]; then
	echo "Must set env var TIKA_VERSION"
	exit 1
fi

if [ -z "$TYPO3_VERSION" ]; then
	echo "Must set env var TYPO3_VERSION"
	exit 1
fi

if [ -z "$EXT_SOLR_VERSION" ]; then
	echo "Must set env var EXT_SOLR_VERSION"
	exit 1
fi


if ! wget --version > /dev/null 2>&1
then
	echo "Couldn't find wget."
	exit 1
fi

# download Tika if not present
if [ ! -d "$TIKA_PATH" ]; then
	mkdir -p "$TIKA_PATH"
fi

if [ ! -f "$TIKA_PATH/tika-app-$TIKA_VERSION.jar" ]; then
	wget "${TIKA_SERVER_SOURCE}tika-app-$TIKA_VERSION.jar" -O "$TIKA_PATH/tika-app-$TIKA_VERSION.jar"
	if [ ! -f "$TIKA_PATH/tika-app-$TIKA_VERSION.jar" ]; then
		echo "Could not download tika-app-$TIKA_VERSION.jar from ${TIKA_SERVER_SOURCE}"
		exit 1
	fi
	echo "Download of tika-app-$TIKA_VERSION.jar successful"
else
	echo "Cached $TIKA_PATH/tika-app-$TIKA_VERSION.jar present"
fi

if [[ $* != *--skip-tika-server-install* ]]; then
    if [ ! -f "$TIKA_PATH/tika-server-$TIKA_VERSION.jar" ]; then
    	wget "${TIKA_SERVER_SOURCE}tika-server-$TIKA_VERSION.jar" -O "$TIKA_PATH/tika-server-$TIKA_VERSION.jar"
    	if [ ! -f "$TIKA_PATH/tika-server-$TIKA_VERSION.jar" ]; then
    		echo "Could not download tika-server-$TIKA_VERSION.jar from ${TIKA_SERVER_SOURCE}"
    		exit 1
    	fi
    	echo "Download of tika-server-$TIKA_VERSION.jar successful"
    else
    	echo "Cached $TIKA_PATH/tika-server-$TIKA_VERSION.jar present"
    fi

    # stop Tika server if one is still running
    if [ -f ./tika_pid ]; then
    	TIKA_PID=$(cat ./tika_pid)
    	echo "Stopping Tika ($TIKA_PID)"
    	kill "$TIKA_PID"
    fi

    # start tika server
    echo "Starting Apache Tika"
    TIKA_PID=$(nohup java -jar "$TIKA_PATH/tika-server-$TIKA_VERSION.jar" > /dev/null 2>&1 & echo $!)
    echo "$TIKA_PID" > tika_pid
    echo "Tika pid: $TIKA_PID"
fi

echo "PWD: $(pwd)"

export TYPO3_PATH_PACKAGES="${EXTENSION_ROOT_PATH}.Build/vendor/"
export TYPO3_PATH_WEB="${EXTENSION_ROOT_PATH}.Build/Web/"

echo "Using extension path $EXTENSION_ROOT_PATH"
echo "Using package path $TYPO3_PATH_PACKAGES"
echo "Using web path $TYPO3_PATH_WEB"

# shellcheck disable=SC2034
export COMPOSER_NO_INTERACTION=1
# Install build tools
echo "Install build tools: "
if ! composer global require \
  sclable/xml-lint \
  scrutinizer/ocular
then
  echo "The build tools(friendsofphp/php-cs-fixer, sclable/xml-lint, scrutinizer/ocular) could not be installed. Please fix this issue."
  exit 1
fi

if [[ $TYPO3_VERSION = *"dev"* ]]; then
    composer config minimum-stability dev
fi

if ! composer require --dev --update-with-dependencies --prefer-source \
  typo3/cms-core:"$TYPO3_VERSION" \
  typo3/cms-backend:"$TYPO3_VERSION" \
  typo3/cms-fluid:"$TYPO3_VERSION" \
  typo3/cms-frontend:"$TYPO3_VERSION" \
  typo3/cms-extbase:"$TYPO3_VERSION" \
  typo3/cms-reports:"$TYPO3_VERSION" \
  typo3/cms-scheduler:"$TYPO3_VERSION" \
  apache-solr-for-typo3/solr:"$EXT_SOLR_VERSION" \
  typo3/cms-tstemplate:"$TYPO3_VERSION"
then
  echo "The test environment could not be installed by composer as expected. Please fix this issue."
  exit 1
fi

export TYPO3_PATH_WEB=$PWD/.Build/Web

mkdir -p "$TYPO3_PATH_WEB"/uploads "$TYPO3_PATH_WEB"/typo3temp

# Setup Solr using install script
if [[ $* != *--skip-solr-install* ]]; then
    chmod u+x "$TYPO3_PATH_WEB"/typo3conf/ext/solr/Resources/Private/Install/install-solr.sh
    "$TYPO3_PATH_WEB"/typo3conf/ext/solr/Resources/Private/Install/install-solr.sh -d "$HOME/solr" -t
fi
