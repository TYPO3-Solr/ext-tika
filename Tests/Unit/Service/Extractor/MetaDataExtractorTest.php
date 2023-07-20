<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace ApacheSolrForTypo3\Tika\Tests\Unit\Service\Extractor;

use ApacheSolrForTypo3\Tika\Service\Extractor\MetaDataExtractor;
use ApacheSolrForTypo3\Tika\Service\Tika\AppService;
use ApacheSolrForTypo3\Tika\Tests\Unit\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Core\Resource\File;

/**
 * Class MetaDataExtractorTest
 *
 * @author Timo Hund <timo.hund@dkd.de>
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
        return [
            'Comments' => 'Licensed to the Apache Software Foundation (ASF) under one or more contributor license agreements.  See the NOTICE file distributed with this work for additional information regarding copyright ownership.',
            'Component 1' => 'Y component: Quantization table 0, Sampling factors 1 horiz/1 vert',
            'Component 2' => 'Cb component: Quantization table 1, Sampling factors 1 horiz/1 vert',
            'Component 3' => 'Cr component: Quantization table 1, Sampling factors 1 horiz/1 vert',
            'Compression Type' => 'Baseline',
            'Content-Length' => '7686',
            'Content-Type' => 'image/jpeg',
            'Data Precision' => '8 bits',
            'File Modified Date' => 'Fri Nov 13 11:32:04 CET 2015',
            'File Name' => 'testJPEG.jpg',
            'File Size' => '7686 bytes',
            'Image Height' => '75 pixels',
            'Image Width' => '100 pixels',
            'Exif Image Height' => '75 pixels',
            'Exif Image Width' => '100 pixels',
            'JPEG Comment' => 'Licensed to the Apache Software Foundation (ASF) under one or more contributor license agreements.  See the NOTICE file distributed with this work for additional information regarding copyright ownership.',
            'Number of Components' => '3',
            'Resolution Units' => 'inch',
            'X Resolution' => '72 dots',
            'X-Parsed-By' => ['org.apache.tika.parser.DefaultParser', 'org.apache.tika.parser.jpeg.JpegParser'],
            'Y Resolution' => '72 dots',
            'comment' => 'Licensed to the Apache Software Foundation (ASF) under one or more contributor license agreements.  See the NOTICE file distributed with this work for additional information regarding copyright ownership.',
            'resourceName' => 'testJPEG.jpg',
            'tiff:BitsPerSample' => '8',
            'tiff:ImageLength' => '75',
            'tiff:ImageWidth' => '100',
            'w' => 'comments: Licensed to the Apache Software Foundation (ASF) under one or more contributor license agreements.  See the NOTICE file distributed with this work for additional information regarding copyright ownership.',
        ];
    }

    /**
     * @test
     */
    public function extractMetaDataReturnsNormalizedMetaData(): void
    {
        $fakedTikaExtractResponse = $this->getFakedExtratorResponseForJGEPImage();

        /** @var MetaDataExtractor|MockObject $metaDataExtractor */
        $metaDataExtractor = $this->getMockBuilder(MetaDataExtractor::class)
            ->setConstructorArgs([[]])
            ->onlyMethods(['getExtractedMetaDataFromTikaService'])
            ->getMock();
        $metaDataExtractor->expects(self::once())->method('getExtractedMetaDataFromTikaService')->willReturn(
            $fakedTikaExtractResponse
        );

        $fileMock = $this->createMock(File::class);
        $metaData = $metaDataExtractor->extractMetaData($fileMock);

        //@todo wrong data type should be int?
        self::assertSame($metaData['width'], '100', 'Could not extract width from meta data');
        self::assertSame($metaData['height'], '75', 'Could not extract height from meta data');
    }

    /**
     * @test
     */
    public function canProcessReturnsFalseForExeFile(): void
    {
        $tikaAppServiceMock = $this->createMock(AppService::class);
        $tikaAppServiceMock->expects(self::once())->method('getSupportedMimeTypes')->willReturn(
            ['application/vnd.sun.xml.writer']
        );

        $exeFileMock = $this->createMock(File::class);
        $exeFileMock->expects(self::any())->method('getMimeType')->willReturn('exe');

        $metaDataExtractor = $this->getMockBuilder(MetaDataExtractor::class)
            ->setConstructorArgs([[]])
            ->onlyMethods(['getExtractor'])->getMock();
        $metaDataExtractor->expects(self::once())->method('getExtractor')->willReturn($tikaAppServiceMock);
        self::assertFalse($metaDataExtractor->canProcess($exeFileMock));
    }

    /**
     * @test
     */
    public function canProcessReturnsTrueForSxwFile(): void
    {
        $tikaAppServiceMock = $this->createMock(AppService::class);
        $tikaAppServiceMock->expects(self::once())->method('getSupportedMimeTypes')->willReturn(
            ['application/vnd.sun.xml.writer']
        );

        $exeFileMock = $this->createMock(File::class);
        $exeFileMock->expects(self::any())->method('getMimeType')->willReturn('application/vnd.sun.xml.writer');

        $metaDataExtractor = $this->getMockBuilder(MetaDataExtractor::class)
            ->setConstructorArgs([[]])
            ->onlyMethods(['getExtractor'])->getMock();
        $metaDataExtractor->expects(self::once())->method('getExtractor')->willReturn($tikaAppServiceMock);
        self::assertTrue($metaDataExtractor->canProcess($exeFileMock));
    }
}
