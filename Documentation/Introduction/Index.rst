..  _introduction:

============
Introduction
============

..  _what-it-does:

What does it do?
================

This extension is a toolkit for detecting and extracting metadata and structured text content from various documents using existing parser libraries.
By default it enriches the mata-data on TYPO3 FAL managed files automatically.

In combination with `EXT:solrfal <https://www.typo3-solr.com/solr-for-typo3/add-ons/typo3-12-lts-feature/file-indexing-for-typo3/>`_
this extension makes it possible to index and search for contents of TYPO3 FAL managed files.

EXT:tika uses `Apache Tika <https://tika.apache.org/>`_ as backing service to extract the data.
So it sends the TYPO3 FAL managed files to the Tika application and uses its response to enrich the data of TYPO3.

Beside of that all the EXT:Tika provides the public API and tools-set to developers to communicate with Apache Tika.


