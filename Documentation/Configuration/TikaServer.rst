..  include:: /Includes.rst.txt
..  index:: Configuration
..  _configuration-tika-server:


Configuration of Tika Server
============================

Requirements
------------

* Running and configured Apache Tika service.
  For example `the docker container <https://hub.docker.com/r/apache/tika>`_

.. note::

      It is possible to run and manage the Tika Server on TYPO3 host, **(not recommended)**.
      if the "Tika Server Jar Path" is provided.

      **This feature is still available but will be removed soon.**
      See: `#135 <https://github.com/TYPO3-Solr/ext-tika/issues/135>`_

.. seealso::

      Refer to our `solr-ddev-site Tika integration <https://github.com/TYPO3-Solr/solr-ddev-site/tree/main/packages/introduction_tika>`_
      to setup the Tika Service via Docker on hosts with ARM-Based processors.

* Setting EXT:tika to use the Apache Tika server connection.

Setup EXT:tika for Tika Server
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Open Extension settings for EXT:tika **General** tab and choose **"Tika Server"** as **Extractor.**


..  figure:: /Images/BE_Settings_ExtensionConfiguration_General.png
    :class: with-shadow
    :alt: Extension configuration for EXT:tika - Choosing Server extractor in General tab

    Extension configuration for EXT:tika - Choosing Server extractor in General tab


After that open the **Server** tab and paste the connection infos/datas according fields.


..  figure:: /Images/BE_Settings_ExtensionConfiguration_Server.png
    :class: with-shadow
    :alt: Extension configuration for EXT:tika - Provide the connection infos/datas for Tika Server

    Extension configuration for EXT:tika - Provide the connection infos/datas for Tika Server

See :ref:`Check if it works <configuration-tika-check>` for test instructions.
