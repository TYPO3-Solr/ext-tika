..  include:: /Includes.rst.txt
..  index:: Configuration
..  _configuration-tika-app:


Configuration of Tika App (not recommended)
===========================================

Requirements
------------

* Java runtime on host TYPO3 is running on. Please refer to the Apache Tika docs or other sources.
* Tika App jar file. See: Download instructions
* Setting EXT:tika to use the downloaded jar file for data extraction.

Download Tika App
~~~~~~~~~~~~~~~~~

Following command will download and verify the integrity of :file:`tika-app-<required-version>.jar` file in :file:`/opt/tika` directory.

.. code-block:: bash

   composer --working-dir="$(composer config vendor-dir)/apache-solr-for-typo3/tika" tika:download:app -- -C -D /opt/tika
   # or alternatively, change into the EXT:tika directory and run
   # composer tika:download:app -- -C -D /opt/tika


Setup EXT:tika for Tika App
~~~~~~~~~~~~~~~~~~~~~~~~~~~

Open Extension settings for EXT:tika **General** tab and choose **"Tika App"** as **Extractor.**


..  figure:: /Images/BE_Settings_ExtensionConfiguration_General.png
    :class: with-shadow
    :alt: Extension configuration for EXT:tika - Choosing App extractor in General tab

    Extension configuration for EXT:tika - Choosing App extractor in General tab


After that open the **Jar** tab and paste the path from downloaded :file:`tika-app-<required-version>.jar` into **Tika App Jar Path** input field.


..  figure:: /Images/BE_Settings_ExtensionConfiguration_Jar.png
    :class: with-shadow
    :alt: Extension configuration for EXT:tika - Provide the path to downloaded App file

    Extension configuration for EXT:tika - Provide the path to downloaded App file


See :ref:`Check if it works <configuration-tika-check>` for test instructions.
