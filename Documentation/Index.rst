.. _start:

=====================
Apache Tika for TYPO3
=====================

:Extension key:
    tika

:Package name:
    apache-solr-for-typo3/tika

:Version:
    |release|

:Language:
    en

:Author:
    Ingo Renner, Markus Friedrich, Rafael Kähm, Timo Hund & Contributors

:License:
   This document is published under the
   `Open Publication License <https://www.opencontent.org/openpub/>`__.

:Rendered:
    |today|

----

Apache Tika is a toolkit for detecting and extracting metadata and structured
text content from various documents using existing parser libraries.

All in all Tika knows/can detect about 1200 file formats and can read about half of them.
These formats include the most common ones: HTML, XML including RSS and ATOM feeds,
Microsoft Office (binary formats and OOXML), OpenDocument (OpenOffice.org),
Apple iWork, PDF, ePUB, RTF, compressed formats like ZIP, audio formats
including MP3, flash flv video, image formats including JPEG and TIFF,
mail box mbox format, and many more.

Apache Tika for TYPO3 provides three services to retrieve information from files:

----

* Text extraction
* Language detection of file contents
* Meta data extraction

All three services can be used with FAL.

It is recommended to use Apache Tika version 1.28 or higher.

Getting Help
============


.. tip::

     If you run into any issues with setting up EXT:tika don't hesitate to ask for help on the `TYPO3 Solr Slack channel <https://typo3.slack.com/messages/ext-solr/>`_

----

**Table of Contents:**

..  toctree::
    :maxdepth: 2
    :titlesonly:

    Introduction/Index
    Configuration/Index
    Editor/Index
    Releases/Index

..  Meta Menu

..  toctree::
    :hidden:

    Sitemap
