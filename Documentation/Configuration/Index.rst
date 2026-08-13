..  _configuration:

=============
Configuration
=============

All the settings for the extension can be made through the TYPO3 Extension Configuration module.

..  figure:: /Images/BE_Settings_ExtensionConfiguration_General.png
    :class: with-shadow
    :alt: Extension configuration for EXT:tika


Extractor
=========

Simply select what service you would like to use, either

* *Tika App(not recommended)*
* *Tika Server(recommended)*

Depending on that, configure the necessary settings for your service on the
according settings tab.

About Tika variants
-------------------

Each variant has its advantages and its drawbacks.

Server - variant (recommended)
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

The Server variant is the best one by set on supported features and is more performant as the App,
but requires additional service and maintenance.

App - variant (not recommended)
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

So for example the App requires Java Runtime to exec and spawn a new java process for each processed file,
but no network traffic for send files via wire.

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

..  toctree::
   :maxdepth: 5
   :titlesonly:

   TikaApp
   TikaServer
   Check
   TikaAllServices

