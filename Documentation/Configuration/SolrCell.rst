..  index:: Configuration
..  _configuration-tika-solr-cell:


Configuration of Solr Cell
==========================

.. warning::

      **Solr Cell is a legacy path.** Apache Solr deprecated the embedded
      Tika backend (``LocalTikaExtractionBackend``) in Solr 9.10 and
      **removed it in Solr 10.0** — from Solr 10 the extraction handler only
      works by delegating to an *external* Apache Tika Server
      (``tikaserver.url``). Apache itself states the embedded backend is
      *"not recommended for use in a production system"* because a crashing
      Tika parser can take down the whole Solr process.

      For new setups, and for Solr 10+, prefer the
      :ref:`Tika Server <configuration-tika-server>` extractor: same
      features, better isolation, and independently patchable (see
      :ref:`Skip Security Checks <configuration-skip-security-checks>` and
      CVE-2025-66516). Solr Cell remains supported here for existing
      installations.

.. note::

      All Apache Solr versions prior v. 9.10.1 are vulnerable
      against `CVE-2025-66516 / CVE-2025-54988 <https://solr.apache.org/security.html#cve-2025-66516-apache-solr-extraction-module-vulnerable-to-xxe-attacks-via-xfa-content-in-pdfs>`_
      please update or apply the proposed mitigation in solrconfig.xml
      and set :php:`$GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['tika']['skipSecurityChecks'] = true` on EXT:tika v 13.1.0+
      if you can not update the Apache Solr server.

      Only do this as a temporary measure and read
      :ref:`Skip Security Checks <configuration-skip-security-checks>` first —
      it re-exposes you to the vulnerability above.


Requirements
------------

* Running and configured Apache Solr service.

.. tip::

  For example `the dkds Hosted-Solr <https://hosted-solr.com/en/>`_

* Setting EXT:tika to use the Apache Solr server connection.

Setup EXT:tika for Solr Server
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Open Extension settings for EXT:tika **General** tab and choose **"Solr Server"** as **Extractor.**


..  figure:: /Images/BE_Settings_ExtensionConfiguration_General.png
    :class: with-shadow
    :alt: Extension configuration for EXT:tika - Choosing Solr Server extractor in General tab

    Extension configuration for EXT:tika - Choosing Solr Server extractor in General tab


After that open the **Solr** tab and paste the connection infos/datas according fields.


..  figure:: /Images/BE_Settings_ExtensionConfiguration_Solr.png
    :class: with-shadow
    :alt: Extension configuration for EXT:tika - Provide the connection infos/datas for Solr Server

    Extension configuration for EXT:tika - Provide the connection infos/datas for Solr Server


.. tip::

       All settings of Solr accept the :php:`%env(<SOME_SOLR_ENV_VAR>)%` syntax like on site-config now.

       If the settings for :php:`solrUsername` or :php:`solrPassword` do not contain the :php:`%env(<SOME_SOLR_ENV_VAR>)%`,
       then they are blinded/hidden, to avoid the accidental release of secrets and credentials via TYPO3 backend configuration Tools like:

       * Extension Settings module
       * Configuration module


See :ref:`Check if it works <configuration-tika-check>` for test instructions.
