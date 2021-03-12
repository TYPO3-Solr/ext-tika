#!/usr/bin/env bash

SCRIPTPATH=$( cd $(dirname $0) ; pwd -P )
EXTENSION_ROOTPATH="$SCRIPTPATH/../../"
TIKA_SERVER_SOURCE="https://archive.apache.org/dist/tika/"

#
# Run this script once to set up a dev/test environment for this extension
# Afterwards simply running cibuild.sh will execute the tests
#

if [[ $* == *--local* ]]; then
    echo -n "Choose a TYPO3 Version (e.g. dev-master, ^9.5.16, ^10.4.2): "
    read typo3Version
    export TYPO3_VERSION=$typo3Version

    echo -n "Choose a EXT:solr Version (e.g. dev-master, dev-release-11.0.x): "
    read extSolrVersion
    export EXT_SOLR_VERSION=$extSolrVersion

    echo -n "Choose a tika Version (e.g. 1.24.1): "
    read tikaVersion
    export TIKA_VERSION=$tikaVersion

    echo -n "Choose a database hostname: "
    read typo3DbHost
    export TYPO3_DATABASE_HOST=$typo3DbHost

    echo -n "Choose a database name: "
    read typo3DbName
    export TYPO3_DATABASE_NAME=$typo3DbName

    echo -n "Choose a database user: "
    read typo3DbUser
    export TYPO3_DATABASE_USERNAME=$typo3DbUser

    echo -n "Choose a database password: "
    read typo3DbPassword
    export TYPO3_DATABASE_PASSWORD=$typo3DbPassword
fi

test -n "$TIKA_PATH" || TIKA_PATH="$HOME/bin"


if [ -z $TIKA_VERSION ]; then
	echo "Must set env var TIKA_VERSION"
	exit 1
fi

if [ -z $TYPO3_VERSION ]; then
	echo "Must set env var TYPO3_VERSION"
	exit 1
fi

wget --version > /dev/null 2>&1
if [ $? -ne "0" ]; then
	echo "Couldn't find wget."
	exit 1
fi



# download Tika if not present
if [ ! -d "$TIKA_PATH" ]; then
	mkdir -p "$TIKA_PATH"
fi

if [ ! -f "$TIKA_PATH/tika-app-$TIKA_VERSION.jar" ]; then
	wget "${TIKA_SERVER_SOURCE}tika-app-$TIKA_VERSION.jar" -O "$TIKA_PATH/tika-app-$TIKA_VERSION.jar"
	test -f "$TIKA_PATH/tika-app-$TIKA_VERSION.jar" || echo "Could not download tika-app-$TIKA_VERSION.jar from ${TIKA_SERVER_SOURCE}" && exit 1
else
	echo "Cached $TIKA_PATH/tika-app-$TIKA_VERSION.jar present"
fi

if [ ! -f "$TIKA_PATH/tika-server-$TIKA_VERSION.jar" ]; then
	wget "${TIKA_SERVER_SOURCE}tika-server-$TIKA_VERSION.jar" -O "$TIKA_PATH/tika-server-$TIKA_VERSION.jar"
	test -f "$TIKA_PATH/tika-server-$TIKA_VERSION.jar" || echo "Could not download tika-server-$TIKA_VERSION.jar from ${TIKA_SERVER_SOURCE}" && exit 1
else
	echo "Cached $TIKA_PATH/tika-server-$TIKA_VERSION.jar present"
fi

# stop Tika server if one is still running
if [ -f ./tika_pid ]; then
	TIKA_PID=cat ./tika_pid
	echo "Stopping Tika ($TIKA_PID)"
	kill $TIKA_PID
fi

# start tika server
echo "Starting Apache Tika"
TIKA_PID=`nohup java -jar "$TIKA_PATH/tika-server-$TIKA_VERSION.jar" > /dev/null 2>&1 & echo $!`
echo $TIKA_PID > tika_pid
echo "Tika pid: $TIKA_PID"

echo "PWD: $(pwd)"

export TYPO3_PATH_PACKAGES="${EXTENSION_ROOTPATH}.Build/vendor/"
export TYPO3_PATH_WEB="${EXTENSION_ROOTPATH}.Build/Web/"

echo "Using extension path $EXTENSION_ROOTPATH"
echo "Using package path $TYPO3_PATH_PACKAGES"
echo "Using web path $TYPO3_PATH_WEB"

if [[ $TYPO3_VERSION = *"dev"* ]]; then
    composer config minimum-stability dev
fi

if [[ $TYPO3_VERSION = *"master"* ]]; then
    TYPO3_MASTER_DEPENDENCIES='nimut/testing-framework:dev-master'
fi

composer require --dev --update-with-dependencies --prefer-source \
  typo3/cms-core:"$TYPO3_VERSION" \
  typo3/cms-backend:"$TYPO3_VERSION" \
  typo3/cms-fluid:"$TYPO3_VERSION" \
  typo3/cms-frontend:"$TYPO3_VERSION" \
  typo3/cms-extbase:"$TYPO3_VERSION" \
  typo3/cms-reports:"$TYPO3_VERSION" \
  typo3/cms-scheduler:"$TYPO3_VERSION" \
  apache-solr-for-typo3/solr:"$EXT_SOLR_VERSION" \
  typo3/cms-tstemplate:"$TYPO3_VERSION" $TYPO3_MASTER_DEPENDENCIES

export TYPO3_PATH_WEB=$PWD/.Build/Web

mkdir -p $TYPO3_PATH_WEB/uploads $TYPO3_PATH_WEB/typo3temp

# Setup Solr using install script
if [[ $* != *--skip-solr-install* ]]; then
    chmod u+x ${TYPO3_PATH_WEB}/typo3conf/ext/solr/Resources/Private/Install/install-solr.sh
    ${TYPO3_PATH_WEB}/typo3conf/ext/solr/Resources/Private/Install/install-solr.sh -d "$HOME/solr" -t
fi