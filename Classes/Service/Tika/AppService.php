<?php
namespace ApacheSolrForTypo3\Tika\Service\Tika;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2015 Ingo Renner <ingo@typo3.org>
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

use ApacheSolrForTypo3\Tika\Utility\ShellUtility;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Utility\CommandUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;


/**
 * A Tika service implementation using the tika-app.jar
 *
 */
class AppService extends AbstractService
{

    /**
     * Service initialization
     *
     * @return void
     */
    protected function initializeService()
    {
        if (!is_file(GeneralUtility::getFileAbsFileName($this->configuration['tikaPath'],
            false))
        ) {
            throw new \RuntimeException(
                'Invalid path or filename for Tika application jar: ' . $this->configuration['tikaPath'],
                1266864929
            );
        }

        if (!CommandUtility::checkCommand('java')) {
            throw new \RuntimeException('Could not find Java', 1421208775);
        }
    }

    /**
     * Gets the Tika server version
     *
     * @return string Tika app version string
     */
    public function getTikaVersion()
    {
        $tikaCommand = CommandUtility::getCommand('java')
            . ' -Dfile.encoding=UTF8' // forces UTF8 output
            . ' -jar ' . escapeshellarg(GeneralUtility::getFileAbsFileName($this->configuration['tikaPath'],
                false))
            . ' -V';

        return shell_exec($tikaCommand);
    }

    /**
     * Takes a file reference and extracts the text from it.
     *
     * @param \TYPO3\CMS\Core\Resource\FileInterface $file
     * @return string
     */
    public function extractText(FileInterface $file)
    {
        $localTempFilePath = $file->getForLocalProcessing(false);
        $tikaCommand = ShellUtility::getLanguagePrefix()
            . CommandUtility::getCommand('java')
            . ' -Dfile.encoding=UTF8' // forces UTF8 output
            . ' -jar ' . escapeshellarg(GeneralUtility::getFileAbsFileName($this->configuration['tikaPath'],
                false))
            . ' -t'
            . ' ' . ShellUtility::escapeShellArgument($localTempFilePath);

        $extractedText = shell_exec($tikaCommand);
        $this->cleanupTempFile($localTempFilePath, $file);

        $this->log('Text Extraction using local Tika', array(
            'file' => $file,
            'tika command' => $tikaCommand,
            'shell output' => $extractedText
        ));

        return $extractedText;
    }

    /**
     * Takes a file reference and extracts its meta data.
     *
     * @param \TYPO3\CMS\Core\Resource\FileInterface $file
     * @return array
     */
    public function extractMetaData(FileInterface $file)
    {
        $localTempFilePath = $file->getForLocalProcessing(false);
        $tikaCommand = ShellUtility::getLanguagePrefix()
            . CommandUtility::getCommand('java')
            . ' -Dfile.encoding=UTF8'
            . ' -jar ' . escapeshellarg(GeneralUtility::getFileAbsFileName($this->configuration['tikaPath'],
                false))
            . ' -m'
            . ' ' . ShellUtility::escapeShellArgument($localTempFilePath);

        $shellOutput = array();
        exec($tikaCommand, $shellOutput);
        $metaData = $this->shellOutputToArray($shellOutput);
        $this->cleanupTempFile($localTempFilePath, $file);

        $this->log('Meta Data Extraction using local Tika', array(
            'file' => $file,
            'tika command' => $tikaCommand,
            'shell output' => $shellOutput,
            'meta data' => $metaData
        ));

        return $metaData;
    }

    /**
     * Takes a file reference and detects its content's language.
     *
     * @param \TYPO3\CMS\Core\Resource\FileInterface $file
     * @return string Language ISO code
     */
    public function detectLanguageFromFile(FileInterface $file)
    {
        $localTempFilePath = $file->getForLocalProcessing(false);
        $language = $this->detectLanguageFromLocalFile($localTempFilePath);

        $this->cleanupTempFile($localTempFilePath, $file);

        return $language;
    }

    /**
     * Takes a string as input and detects its language.
     *
     * @param string $input
     * @return string Language ISO code
     */
    public function detectLanguageFromString($input)
    {
        $tempFilePath = GeneralUtility::tempnam('Tx_Tika_AppService_DetectLanguage');
        file_put_contents($tempFilePath, $input);

        // detect language
        $language = $this->detectLanguageFromLocalFile($tempFilePath);

        // cleanup
        unlink($tempFilePath);

        return $language;
    }

    /**
     * The actual language detection
     *
     * @param string $localFilePath Path to a local file
     * @return string The file content's language
     */
    protected function detectLanguageFromLocalFile($localFilePath)
    {
        $tikaCommand = ShellUtility::getLanguagePrefix()
            . CommandUtility::getCommand('java')
            . ' -Dfile.encoding=UTF8'
            . ' -jar ' . escapeshellarg(GeneralUtility::getFileAbsFileName($this->configuration['tikaPath'],
                false))
            . ' -l'
            . ' ' . ShellUtility::escapeShellArgument($localFilePath);

        $language = trim(shell_exec($tikaCommand));

        $this->log('Language Detection using local Tika', array(
            'file' => $localFilePath,
            'tika command' => $tikaCommand,
            'shell output' => $language
        ));

        return $language;
    }

    /**
     * Takes shell output from exec() and turns it into an array of key => value
     * pairs.
     *
     * @param array $shellOutput An array containing shell output from exec() with one line per entry
     * @return array Key => value pairs
     */
    protected function shellOutputToArray(array $shellOutput)
    {
        $metaData = array();

        foreach ($shellOutput as $line) {
            list($key, $value) = explode(':', $line, 2);
            $value = trim($value);

            if (in_array($key, array(
                'dc',
                'dcterms',
                'meta',
                'tiff',
                'xmp',
                'xmpTPg',
                'xmpDM'
            ))) {
                // Dublin Core metadata and co
                $keyPrefix = $key;
                list($key, $value) = explode(':', $value, 2);

                $key = $keyPrefix . ':' . $key;
                $value = trim($value);
            }

            if (array_key_exists($key, $metaData)) {
                if ($metaData[$key] == $value) {
                    // first duplicate key hit, but also duplicate value
                    continue;
                }

                // allow a meta data key to appear multiple times
                if (!is_array($metaData[$key])) {
                    $metaData[$key] = array($metaData[$key]);
                }

                // but do not allow duplicate values
                if (!in_array($value, $metaData[$key])) {
                    $metaData[$key][] = $value;
                }
            } else {
                $metaData[$key] = $value;
            }
        }

        return $metaData;
    }
}
