..  include:: /Includes.rst.txt

..  _configuration:

=============
Configuration
=============

All the settings for the extension can be made through the TYPO3 Extension Configuration module.

..  figure:: /Images/BE_Settings_ExtensionConfiguration_General.png
    :class: with-shadow
    :alt: Extension configuration for EXT:tika
    :width: 60%


Extractor
=========

Simply select what service you would like to use, either

* *Tika App(not recommended)*
* *Tika Server(recommended)*
* *Solr Server*.

Depending on that, configure the necessary settings for your service on the
according settings tab.

About Tika variants
-------------------

Each variant has its advantages and its drawbacks.

App - variant (not recommended)
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

So for example the App requires Java Runtime to exec and spawn a new java process for each processed file,
but no network traffic for send files via wire.

Solr Cell - variant
~~~~~~~~~~~~~~~~~~~

Apache Solr Content Extraction Library (Solr Cell) variant does not support all the features supported by the App and by Server variants,
but does not require to run and maintain any additional service/stack, if EXT:solr is already configured.
Any connection/core used by EXT:solr can be reused there.
Possible implications can be found on `Apache Solr docs page <https://solr.apache.org/guide/solr/latest/indexing-guide/indexing-with-tika.html#solr-cell-performance-implications>`_

Server - variant (recommended)
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

The Server variant is the best one by set on supported features and is more performant as the App,
but requires additional service and maintenance.

Enable Logging
==============

Enables the logging for extraction actions.

Show Tika Backend Module
========================

Enables a Tika module within the Solr backend module (experimental, only works with Tika server, will be removed.)

Exclude mime types
==================

Expects a list of mime types to be excluded in metadata extraction.

File size limit...
==================

Expects a file size limit in MB when a file should be processed. (Defaults to 500)

Enable meta data extraction
===========================

Enables MetaDataExtractor, including LanguageDetector, if available. (Default: true)
Useful on frequent file movements or mass file processing or if metadata must not be overridden.

..  toctree::
   :maxdepth: 5
   :titlesonly:

   TikaApp
   TikaServer
   SolrCell
   Check
   TikaAllServices

