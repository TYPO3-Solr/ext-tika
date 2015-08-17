#!/usr/bin/env bash

test -n "$TIKA_PATH" || TIKA_PATH="$HOME/bin/"

# download Tika if not present
if [ ! -d "$TIKA_PATH" ]; then
	mkdir -p "$TIKA_PATH"
fi
if [ ! -f "$TIKA_PATH/tika-app-$TIKA_VERSION.jar" ]; then
	wget "http://apache.osuosl.org/tika/tika-app-$TIKA_VERSION.jar" -O "$TIKA_PATH/tika-app-$TIKA_VERSION.jar"
fi
if [ ! -f "$TIKA_PATH/tika-server-$TIKA_VERSION.jar" ]; then
	wget "http://apache.osuosl.org/tika/tika-server-$TIKA_VERSION.jar" -O "$TIKA_PATH/tika-server-$TIKA_VERSION.jar"
fi

# start tika server
java -jar "$TIKA_PATH/tika-server-$TIKA_VERSION.jar" &

cd ..

# clone TYPO3
git clone --single-branch --branch $TYPO3_BRANCH --depth 1 https://github.com/TYPO3/TYPO3.CMS.git typo3_core
mv typo3_core/* .
composer self-update
composer install
mkdir -p uploads typo3temp typo3conf/ext/tika

# clone EXT:solr
git clone --single-branch --branch master --depth 1 https://github.com/TYPO3-Solr/ext-solr.git solr

mv solr typo3conf/ext/
cp -R ext-tika/* typo3conf/ext/tika/

cd -