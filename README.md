# Apache Tika for TYPO3

[![Build Status](https://travis-ci.org/TYPO3-Solr/ext-tika.svg?branch=master)](https://travis-ci.org/TYPO3-Solr/ext-tika)
[![Latest Stable Version](https://poser.pugx.org/apache-solr-for-typo3/tika/v/stable)](https://packagist.org/packages/apache-solr-for-typo3/tika)
[![Latest Unstable Version](https://poser.pugx.org/apache-solr-for-typo3/tika/v/unstable)](https://packagist.org/packages/apache-solr-for-typo3/tika)
[![License](https://poser.pugx.org/apache-solr-for-typo3/tika/license)](https://packagist.org/packages/apache-solr-for-typo3/tika)
[![Monthly Downloads](https://poser.pugx.org/apache-solr-for-typo3/tika/d/monthly)](https://packagist.org/packages/apache-solr-for-typo3/tika)

A TYPO3 CMS extension that provides Apache Tika functionality including

* text extraction
* meta data extraction
* language detection (from strings or files)

Tika can be used as standalone Tika app/jar, Tika server, and via SolrCell integrated in Apache Solr.

We're open for [contributions](#Contributions) !

Please find further information regarding Apache Tika on the [project's homepage](http://tika.apache.org)


## Continuous Integration

We use travis ci for continuous integration. To run the whole test suite locally for one TYPO3 & Tika Version
do the following:

```bash
export TIKA_VERSION="1.11"
export TIKA_PATH="/tmp/tika"
export TYPO3_VERSION="dev-master"
chmod +x ./Build/Test/*.sh
./Build/Test/bootstrap.sh
./Build/Test/cibuild.sh
```

## <a name="Contributions"></a>Contributions

1. Fork the repository
2. Clone repository
3. Create a new branch
4. Make your changes
5. Commit your changes to your fork. In your commit message refer to the issue number if there is already one, e.g. `[BUGFIX] short description of fix (resolves #4711)`
6. Submit a Pull Request (here are some hints on [How to write the perfect pull request](https://github.com/blog/1943-how-to-write-the-perfect-pull-request))

### Keep your fork in sync with original repository

1. git remote add upstream https://github.com/TYPO3-Solr/ext-tika.git
2. git fetch upstream
3. git checkout master
4. git merge upstream/master
5. git push origin master