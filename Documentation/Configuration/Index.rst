..  include:: /Includes.rst.txt

..  _configuration:

=============
Configuration
=============

All the settings for the extension can be made through the TYPO3 Extension Configuration module.
Simply select what service you would like to use, either

* *Tika App(not recommended)*
* *Tika Server(recommended)*
* *Solr Server*.

Depending on that, configure the necessary settings for your service on the
according settings tab.

About Tika variants
===================

Each variant has its advantages and its drawbacks.

App - variant (not recommended)
-------------------------------

So for example the App requires Java Runtime to exec and spawn a new java process for each processed file,
but no network traffic for send files via wire.

Solr Cell - variant
-------------------

Apache Solr Content Extraction Library (Solr Cell) variant does not support all the features supported by the App and by Server variants,
but does not require to run and maintain any additional service/stack, if EXT:solr is already configured.
Any connection/core used by EXT:solr can be reused there.
Possible implications can be found on `Apache Solr docs page <https://solr.apache.org/guide/solr/latest/indexing-guide/indexing-with-tika.html#solr-cell-performance-implications>`_

Server - variant (recommended)
------------------------------

The Server variant is the best one by set on supported features and is more performant as the App,
but requires additional service and maintenance.

..  toctree::
   :maxdepth: 5
   :titlesonly:

   TikaApp
   TikaServer
   SolrCell
   Check
   TikaAllServices

