<?php
namespace ApacheSolrForTypo3\Tika\Tests\Unit\Service\Extractor;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2015 Timo Schmidt <timo.schmidt@dkd.de>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use ApacheSolrForTypo3\Tika\Service\Extractor\MetaDataExtractor;
use ApacheSolrForTypo3\Tika\Service\Tika\ServerService;
use ApacheSolrForTypo3\Tika\Tests\Unit\UnitTestCase;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;


/**
 * Class MetaDataExtractorTest
 *
 */
class MetaDataExtractorTest extends UnitTestCase
{

    /**
     * Returns a faked extractor response of a jpeg image.
     *
     * @return array
     */
    protected function getFakedExtratorResponseForJGEPImage()
    {
        return array(
            'Comments' => "Licensed to the Apache Software Foundation (ASF) under one or more contributor license agreements.  See the NOTICE file distributed with this work for additional information regarding copyright ownership.",
            'Component 1' => "Y component: Quantization table 0, Sampling factors 1 horiz/1 vert",
            'Component 2' => "Cb component: Quantization table 1, Sampling factors 1 horiz/1 vert",
            'Component 3' => "Cr component: Quantization table 1, Sampling factors 1 horiz/1 vert",
            'Compression Type' => "Baseline",
            'Content-Length' => "7686",
            'Content-Type' => "image/jpeg",
            'Data Precision' => "8 bits",
            'File Modified Date' => "Fri Nov 13 11:32:04 CET 2015",
            'File Name' => "testJPEG.jpg",
            'File Size' => "7686 bytes",
            'Image Height' => "75 pixels",
            'Image Width' => "100 pixels",
            'JPEG Comment' => "Licensed to the Apache Software Foundation (ASF) under one or more contributor license agreements.  See the NOTICE file distributed with this work for additional information regarding copyright ownership.",
            'Number of Components' => "3",
            'Resolution Units' => "inch",
            'X Resolution' => "72 dots",
            'X-Parsed-By' => array("org.apache.tika.parser.DefaultParser", "org.apache.tika.parser.jpeg.JpegParser"),
            'Y Resolution' => "72 dots",
            'comment' => "Licensed to the Apache Software Foundation (ASF) under one or more contributor license agreements.  See the NOTICE file distributed with this work for additional information regarding copyright ownership.",
            'resourceName' => "testJPEG.jpg",
            'tiff:BitsPerSample' => "8",
            'tiff:ImageLength' => "75",
            'tiff:ImageWidth' => "100",
            'w' => "comments: Licensed to the Apache Software Foundation (ASF) under one or more contributor license agreements.  See the NOTICE file distributed with this work for additional information regarding copyright ownership."
        );
    }

    /**
     * @test
     */
    public function extractMetaDataReturnsNormalizedMetaData()
    {
        $fakedTikaExtractResponse = $this->getFakedExtratorResponseForJGEPImage();

        /** @var $metaDataExtractor \ApacheSolrForTypo3\Tika\Service\Extractor\MetaDataExtractor */
        $metaDataExtractor = $this->getMock('ApacheSolrForTypo3\\Tika\\Service\\Extractor\\MetaDataExtractor',
            array('getExtractedMetaDataFromTikaService'));
        $metaDataExtractor->expects($this->once())->method('getExtractedMetaDataFromTikaService')->will($this->returnValue(
            $fakedTikaExtractResponse
        ));

        $fileMock = $this->getDumbMock('TYPO3\CMS\Core\Resource\File');
        $metaData = $metaDataExtractor->extractMetaData($fileMock);

        //@todo wrong data type should be int?
        $this->assertSame($metaData['width'], "100", 'Could not extract width from meta data');
        $this->assertSame($metaData['height'], "75", 'Could not extract height from meta data');
    }

    /**
     * @test
     */
    public function canProcessReturnsFalseForExeFile()
    {
        $exeFileMock = $this->getDumbMock('TYPO3\CMS\Core\Resource\File');
        $exeFileMock->expects($this->any())->method('getProperty')->with('extension')->will($this->returnValue('exe'));

        $metaDataExtractor = new MetaDataExtractor();
        $this->assertFalse($metaDataExtractor->canProcess($exeFileMock));
    }

    /**
     * @test
     */
    public function canProcessReturnsTrueForSxwFile()
    {
        $exeFileMock = $this->getDumbMock('TYPO3\CMS\Core\Resource\File');
        $exeFileMock->expects($this->any())->method('getProperty')->with('extension')->will($this->returnValue('sxw'));

        $metaDataExtractor = new MetaDataExtractor();
        $this->assertTrue($metaDataExtractor->canProcess($exeFileMock));
    }
}