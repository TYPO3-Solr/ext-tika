#!/usr/bin/env bash

set -x

# download Tika
mkdir -p "$HOME/tika"
parallel --gnu 'wget "http://apache.osuosl.org/tika/tika-{}-$TIKA_VERSION.jar" -O "$HOME/tika/tika-{}-$TIKA_VERSION.jar"' ::: app server

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
cp ext-tika/* typo3conf/ext/tika/

cd -