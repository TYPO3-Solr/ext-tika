#!/usr/bin/env bash

#
# Run this script once to set up a dev/test environment for this extension
# Afterwards simply running cibuild.sh will execute the tests
#

if [[ $* == *--local* ]]; then
    echo -n "Choose a TYPO3 Version (e.g. dev-master,~6.2.17,~7.6.2): "
    read typo3Version
    export TYPO3_VERSION=$typo3Version

    echo -n "Choose a tika Version (e.g. 1.14): "
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
	wget "http://apache.osuosl.org/tika/tika-app-$TIKA_VERSION.jar" -O "$TIKA_PATH/tika-app-$TIKA_VERSION.jar"
else
	echo "Cached $TIKA_PATH/tika-app-$TIKA_VERSION.jar present"
fi
if [ ! -f "$TIKA_PATH/tika-server-$TIKA_VERSION.jar" ]; then
	wget "http://apache.osuosl.org/tika/tika-server-$TIKA_VERSION.jar" -O "$TIKA_PATH/tika-server-$TIKA_VERSION.jar"
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

composer require typo3/cms="$TYPO3_VERSION"
composer require apache-solr-for-typo3/solr:"^4.0.0 || dev-master"

# Restore composer.json
git checkout composer.json

export TYPO3_PATH_WEB=$PWD/.Build/Web

mkdir -p $TYPO3_PATH_WEB/uploads $TYPO3_PATH_WEB/typo3temp
