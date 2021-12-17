.. ====================
.. FOR YOUR INFORMATION
.. --------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: Includes.txt


.. _start:

=====================
Apache Tika for TYPO3
=====================

.. only:: html

	:Classification:
		tika

	:Version:
		10.0.1

	:Language:
		en

	:Description:
		Apache Tika for TYPO3

	:Keywords:
		apache, tika, meta, data, DAM, files, FAL, solr, server, language, content, detection, extraction

	:Copyright:
		2009-2015

	:Author:
		Ingo Renner

	:Email:
		ingo@typo3.org

	:License:
		This document is published under the Open Content License
		available from http://www.opencontent.org/opl.shtml

	:Rendered:
		|today|

	The content of this document is related to TYPO3,
	a GNU/GPL CMS/Framework available from `typo3.org <http://typo3.org/>`_.


What does it do?
================

Apache Tika is a toolkit for detecting and extracting metadata and structured
text content from various documents using existing parser libraries.

All in all Tika knows/can detect about 1200 file formats and can read about half of them.
These formats include the most common ones: HTML, XML including RSS and ATOM feeds,
Microsoft Office (binary formats and OOXML), OpenDocument (OpenOffice.org),
Apple iWork, PDF, ePUB, RTF, compressed formats like ZIP, audio formats
including MP3, flash flv video, image formats including JPEG and TIFF,
mail box mbox format, and many more.

Apache Tika for TYPO3 provides three services to retrieve information from files:

* Text extraction
* Language detection of file contents
* Meta data extraction

All three services can be used with FAL.

It is recommended to use Apache Tika version 1.11 or higher.


Configuration
=============

All the settings for the extension can be made through the TYPO3 Extension Manager.
Simply select what service you would like to use, either *Tika App*, *Tika Server*
or *Solr Server*.
Depending on that, configure the necessary settings for your service on the
according settings tab.

When done, check the TYPO3 system status report to validate your settings.


Configuration of Tika App
=========================

General information about how to configure the Tika App can be found in the
`official documentation <https://tika.apache.org/1.16/configuring.html>`_

In case you want to exclude certain mime types from beeing processed by Tika,
you can do the following:

Create the file :file:`/etc/tika/tika-config.xml` with this content:

.. code-block:: xml

   <?xml version="1.0" encoding="UTF-8"?>
   <properties>
     <parsers>
       <parser class="org.apache.tika.parser.DefaultParser">
         <mime-exclude>application/zip</mime-exclude>
       </parser>
       <parser class="org.apache.tika.parser.EmptyParser">
         <mime>application/zip</mime>
       </parser>
     </parsers>
   </properties>

This tells Tika to exclude zip files from DefaultParser and use EmptyParser instead,
who does basically nothing.

Then add one line to :file:`/etc/security/pam_env.con`:

.. code-block:: bash

   TIKA_CONFIG     DEFAULT="/etc/tika/tika-config.xml"

This sets a global environment variable where Tika shold look for more configuration.


Getting Help
============

First check the TYPO3 system status report for any errors reported by the extension.
You will find them as reported from Apache Tika. The extension checks whether you
have Java installed when using the Tika app or Tika server. It will also check
your configuration, whether the configured paths for Tika app and Tika server are
available and whether Tika Server and Solr server can be reached depending on what
you're using.

If you run into any issues with setting up EXT:tika don't hesitate to ask for help on the
`TYPO3 Solr Slack channel <https://typo3.slack.com/messages/ext-solr/>`_


.. toctree::
   :maxdepth: 3
   :titlesonly:
   :glob:

   Releases/Index
