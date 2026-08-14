..  index:: Configuration
..  _configuration-tika-server:

.. note::

      All Apache Tika Server versions prior v. 3.2.2 are vulnerable
      against `CVE-2025-54988 <https://nvd.nist.gov/vuln/detail/CVE-2025-54988>`_
      please update the Tika server.

Configuration of Tika Server
============================

Requirements
------------

Tika Server v3.2.2+ is required.

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


After that open the **Server** tab and paste the connection infos/data according fields.


..  figure:: /Images/BE_Settings_ExtensionConfiguration_Server.png
    :class: with-shadow
    :alt: Extension configuration for EXT:tika - Provide the connection infos/data for Tika Server

    Extension configuration for EXT:tika - Provide the connection infos/data for Tika Server

Authentication and timeouts
~~~~~~~~~~~~~~~~~~~~~~~~~~~

The **Server** tab also offers a few fields to tune the HTTP client used to talk to the Tika Server:

*   **Username**, then **Password**: credentials for HTTP Basic-Auth, in case the Tika Server is protected by a reverse proxy.
    Both fields are masked in the Extension Configuration module the same way EXT:tika previously masked the Solr Cell credentials.
*   **Connect timeout (ms)** / **Request timeout (ms)**: how long to wait for the connection to be established, and for the whole request/response, before giving up.
    Both accept `100` to `60000` milliseconds and are clamped to that range; defaults are `2000` and `10000`.

Advanced: raw Guzzle client options
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Everything above only covers the settings most installations need, exposed as typed, validated fields in the Extension Configuration module.
For anything else the underlying `Guzzle HTTP client <https://docs.guzzlephp.org/en/stable/request-options.html>`_ supports (TLS verification, a proxy, client certificates, ...), set it directly in your installation's :file:`config/system/additional.php` (or :file:`config/system/settings.php`):

..  code-block:: php
    :caption: config/system/additional.php

    $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['tika']['tikaServerGuzzleOptions'] = [
        'verify' => false,
        'proxy' => 'http://proxy.example.com:8080',
        'headers' => [
            'X-My-Custom-Header' => 'foo',
        ],
    ];

Keys here must already be valid Guzzle request options and take precedence over everything computed from the Extension Configuration module - including overriding the computed :php:`auth`, :php:`connect_timeout` or :php:`timeout` if set explicitly under the same key names.

The array is merged in with :php:`\TYPO3\CMS\Core\Utility\ArrayUtility::mergeRecursiveWithOverrule()`, the same mechanism TYPO3 itself uses to merge :file:`additional.php` over :file:`LocalConfiguration.php`.
That means nested options merge key by key instead of being replaced wholesale - the :php:`headers` example above adds :php:`X-My-Custom-Header` without losing the default :php:`User-Agent` header that TYPO3 already sets for every outgoing request.

See :ref:`Check if it works <configuration-tika-check>` for test instructions.
