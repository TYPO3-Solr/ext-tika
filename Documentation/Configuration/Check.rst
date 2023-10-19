..  include:: /Includes.rst.txt
..  index:: Configuration
..  _configuration-tika-check:

Check if it works
=================

TYPO3 Reports
-------------

First of all check the TYPO3 Reports module for any errors reported by the extension.
You will find them as reported from "Apache Tika".

The extension checks whether you have Java installed when using the Tika app or Tika server.

It will also check your configuration, whether the configured paths for Tika app and Tika server are
available and whether Tika Server and Solr server can be reached depending on what you're using.

If all is configured as expected, you'll get following in TYPO3 Reports:

..  figure:: /Images/BE_Reports_Tika_OK.png
    :class: with-shadow
    :alt: EXT:tika Check configs - OK

    EXT:tika Check configs - OK

Real test via Tika Preview
--------------------------

If all is fine, you can try to extract really via :ref:`Tika Preview <index-editors-and-tika-preview>`
