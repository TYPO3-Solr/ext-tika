#!/usr/bin/env bash

SCRIPTPATH=$( cd $(dirname $0) ; pwd -P )
EXTENSION_ROOTPATH="$SCRIPTPATH/../../"

#
# Run this script once to set up a dev/test environment for this extension
# Afterwards simply running cibuild.sh will execute the tests
#

if [[ $* == *--local* ]]; then
    echo -n "Choose a TYPO3 Version (e.g. dev-master,~6.2.17,~7.6.2): "
    read typo3Version
    export TYPO3_VERSION=$typo3Version

    echo -n "Choose a EXT:solr Version (e.g. dev-master,~3.1.1): "
    read extSolrVersion
    export EXT_SOLR_VERSION=$extSolrVersion

    echo -n "Choose a tika Version (e.g. 1.15): "
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

if [ -z $EXT_SOLR_VERSION ]; then
  echo "Must set env var EXT_SOLR_VERSION"
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

if ! java -version 2>&1 >/dev/null ; then
  echo "Java is not available, please make sure the test environment has Java installed."
  exit 1
fi

# download Tika if not present
if [ ! -d "$TIKA_PATH" ]; then
  mkdir -p "$TIKA_PATH"
fi
if [ ! -f "$TIKA_PATH/tika-app-$TIKA_VERSION.jar" ]; then
  wget "https://archive.apache.org/dist/tika/tika-app-$TIKA_VERSION.jar" -O "$TIKA_PATH/tika-app-$TIKA_VERSION.jar"
else
  echo "Cached $TIKA_PATH/tika-app-$TIKA_VERSION.jar present"
fi
if [ ! -f "$TIKA_PATH/tika-server-$TIKA_VERSION.jar" ]; then
  wget "https://archive.apache.org/dist/tika/tika-server-$TIKA_VERSION.jar" -O "$TIKA_PATH/tika-server-$TIKA_VERSION.jar"
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
TIKA_PID=`nohup java -jar "$TIKA_PATH/tika-server-$TIKA_VERSION.jar" > tika_log 2>&1 & echo $!`

# check if Tika process is really running
if ps -p "$TIKA_PID" > /dev/null
then
  echo $TIKA_PID > tika_pid
  echo "Tika pid: $TIKA_PID"
else
  echo "Tika start failure"
  cat tika_log
  exit 1
fi

echo "PWD: $(pwd)"

export TYPO3_PATH_PACKAGES="${EXTENSION_ROOTPATH}.Build/vendor/"
export TYPO3_PATH_WEB="${EXTENSION_ROOTPATH}.Build/Web/"

echo "Using extension path $EXTENSION_ROOTPATH"
echo "Using package path $TYPO3_PATH_PACKAGES"
echo "Using web path $TYPO3_PATH_WEB"

composer require --dev --update-with-dependencies --prefer-source \
  apache-solr-for-typo3/solr:"$EXT_SOLR_VERSION"

export TYPO3_PATH_WEB=$PWD/.Build/Web

mkdir -p $TYPO3_PATH_WEB/uploads $TYPO3_PATH_WEB/typo3temp

# Setup Solr using install script
chmod u+x ${TYPO3_PATH_WEB}/typo3conf/ext/solr/Resources/Private/Install/install-solr.sh
${TYPO3_PATH_WEB}/typo3conf/ext/solr/Resources/Private/Install/install-solr.sh -d "$HOME/solr" -t