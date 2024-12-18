..  index:: Configuration
..  _configuration-tika-services:


Configuring Tika Services
=========================

**General information about how to configure the Tika Services can be found in the**
`official Tika documentation <https://tika.apache.org/1.28/configuring.html>`_

.. tip::

        The :file:`tika-config.xml` can be applied on all variants of Tika services.

In case you want to exclude certain mime types from being processed by Tika,
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

Apply tika-config.xml
---------------------

.. tip::

        `Tika docs "Using a Tika Configuration XML file" <https://tika.apache.org/1.28/configuring.html#Using_a_Tika_Configuration_XML_file>`_
        provides information how to apply the tika-config.xml file, however pan_env can make the things simpler.

        Adding following line to :file:`/etc/security/pam_env.con`, makes the TIKA_CONFIG env variable global on host.

        .. code-block:: bash

           TIKA_CONFIG     DEFAULT="/etc/tika/tika-config.xml"


