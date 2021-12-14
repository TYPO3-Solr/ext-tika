<?php
namespace ApacheSolrForTypo3\Tika\Service\Tika;

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

use ApacheSolrForTypo3\Tika\Utility\FileUtility;
use ApacheSolrForTypo3\Tika\Utility\ShellUtility;
use RuntimeException;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Utility\CommandUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * A Tika service implementation using the tika-app.jar
 *
 * @copyright (c) 2015 Ingo Renner <ingo@typo3.org>
 */
class AppService extends AbstractService
{
    protected const JAVA_COMMAND_OPTIONS_REGEX = '/-D(?P<property>[\w.]+)=(?P<value>"[^"]+"|\'[^\']+\'|[^\\s\'"]+)/';

    /**
    * @var array
    */
    protected static $supportedMimeTypes = [];

    /**
     * Service initialization
     *
     * @return void
     */
    protected function initializeService()
    {
        if (!is_file(FileUtility::getAbsoluteFilePath($this->configuration['tikaPath']))
        ) {
            throw new RuntimeException(
                'Invalid path or filename for Tika application jar: ' . $this->configuration['tikaPath'],
                1266864929
            );
        }

        if (!CommandUtility::checkCommand('java')) {
            throw new RuntimeException('Could not find Java', 1421208775);
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
            . $this->getAdditionalCommandOptions()
            . ' -jar ' . escapeshellarg(FileUtility::getAbsoluteFilePath($this->configuration['tikaPath']))
            . ' -V';

        return shell_exec($tikaCommand);
    }

    /**
     * Takes a file reference and extracts the text from it.
     *
     * @param FileInterface $file
     * @return string
     */
    public function extractText(FileInterface $file)
    {
        $localTempFilePath = $file->getForLocalProcessing(false);
        $tikaCommand = ShellUtility::getLanguagePrefix()
            . CommandUtility::getCommand('java')
            . ' -Dfile.encoding=UTF8' // forces UTF8 output
            . $this->getAdditionalCommandOptions()
            . ' -jar ' . escapeshellarg(FileUtility::getAbsoluteFilePath($this->configuration['tikaPath']))
            . ' -t'
            . ' ' . ShellUtility::escapeShellArgument($localTempFilePath);

        $extractedText = shell_exec($tikaCommand);

        $this->log(
            'Text Extraction using local Tika',
            [
                'file' => $file,
                'tika command' => $tikaCommand,
                'shell output' => $extractedText
            ]
        );

        return $extractedText;
    }

    /**
     * Takes a file reference and extracts its meta data.
     *
     * @param FileInterface $file
     * @return array
     */
    public function extractMetaData(FileInterface $file)
    {
        $localTempFilePath = $file->getForLocalProcessing(false);
        $tikaCommand = ShellUtility::getLanguagePrefix()
            . CommandUtility::getCommand('java')
            . ' -Dfile.encoding=UTF8'
            . $this->getAdditionalCommandOptions()
            . ' -jar ' . escapeshellarg(FileUtility::getAbsoluteFilePath($this->configuration['tikaPath']))
            . ' -m'
            . ' ' . ShellUtility::escapeShellArgument($localTempFilePath);

        $shellOutput = [];
        exec($tikaCommand, $shellOutput);
        $metaData = $this->shellOutputToArray($shellOutput);

        $this->log(
            'Meta Data Extraction using local Tika',
            [
                'file' => $file,
                'tika command' => $tikaCommand,
                'shell output' => $shellOutput,
                'meta data' => $metaData
            ]
        );

        return $metaData;
    }

    /**
     * Takes a file reference and detects its content's language.
     *
     * @param FileInterface $file
     * @return string Language ISO code
     */
    public function detectLanguageFromFile(FileInterface $file)
    {
        $localTempFilePath = $file->getForLocalProcessing(false);

        return $this->detectLanguageFromLocalFile($localTempFilePath);
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
            . $this->getAdditionalCommandOptions()
            . ' -jar ' . escapeshellarg(FileUtility::getAbsoluteFilePath($this->configuration['tikaPath']))
            . ' -l'
            . ' ' . ShellUtility::escapeShellArgument($localFilePath);

        $language = trim(shell_exec($tikaCommand));

        $this->log(
            'Language Detection using local Tika',
            [
                'file' => $localFilePath,
                'tika command' => $tikaCommand,
                'shell output' => $language
            ]
        );

        return $language;
    }

    /**
     * @return array
     */
    public function getSupportedMimeTypes()
    {
        if(is_array(self::$supportedMimeTypes) && count(self::$supportedMimeTypes) > 0) {
            return self::$supportedMimeTypes;
        }

        self::$supportedMimeTypes = $this->buildSupportedMimeTypes();

        return self::$supportedMimeTypes;
    }

    /**
     * @return array
     */
    public function buildSupportedMimeTypes()
    {
        $mimeTypeOutput = $this->getMimeTypeOutputFromTikaJar();
        $coreTypes = [];
        preg_match_all('/^[^\s]*/im', $mimeTypeOutput, $coreTypes);

        $aliasTypes = [];
        preg_match_all('/^[\s]*alias:[\s]*.*/im', $mimeTypeOutput, $aliasTypes);

        $supportedTypes = $coreTypes[0];
        foreach($aliasTypes[0] as $aliasType) {
            $supportedTypes[] = trim(str_replace('alias:','', $aliasType));
        }

        $supportedTypes = array_filter($supportedTypes);
        asort($supportedTypes);
        return $supportedTypes;
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
        $metaData = [];

        foreach ($shellOutput as $line) {
            list($key, $value) = explode(':', $line, 2);
            $value = trim($value);

            if (in_array($key, [
                'dc',
                'dcterms',
                'meta',
                'tiff',
                'xmp',
                'xmpTPg',
                'xmpDM'
            ])) {
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
                    $metaData[$key] = [$metaData[$key]];
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

    /**
     * The app is available when the jar can be opened
     *
     * @return boolean
     */
    public function isAvailable()
    {
        $tikaFileExists = is_file(FileUtility::getAbsoluteFilePath($this->configuration['tikaPath']));
        if (!$tikaFileExists) {
            return false;
        }

        $canCallJava = CommandUtility::checkCommand('java');
        if (!$canCallJava) {
            return false;
        }

        return true;
    }

    /**
     * @return string
     */
    protected function getMimeTypeOutputFromTikaJar(): string
    {
        $tikaCommand = ShellUtility::getLanguagePrefix()
            . CommandUtility::getCommand('java')
            . ' -Dfile.encoding=UTF8'
            . $this->getAdditionalCommandOptions()
            . ' -jar ' . escapeshellarg(FileUtility::getAbsoluteFilePath($this->configuration['tikaPath']))
            . ' --list-supported-types';

        return trim(shell_exec($tikaCommand));
    }

    /**
     * Parse additional Java command options.
     *
     * Reads the configuration value `javaCommandOptions` and tries to parse it to a
     * safe argument string. For safety reasons, only the following variants are
     * allowed (multiple separated by space):
     *
     * -Dfoo=bar
     * -Dfoo='hello world'
     * -Dfoo="hello world"
     *
     * @return string Parsed additional Java command options
     */
    protected function getAdditionalCommandOptions(): string
    {
        $commandOptions = trim((string)($this->configuration['javaCommandOptions'] ?? ''));

        // Early return if no additional command options are configured
        // or configuration does not match required pattern (only -D parameter is supported)
        if ('' === $commandOptions || !preg_match_all(self::JAVA_COMMAND_OPTIONS_REGEX, $commandOptions, $matches)) {
            return '';
        }

        // Combine matched command options with escaped argument value
        $commandOptionsString = '';
        foreach (array_combine($matches['property'], $matches['value']) as $property => $unescapedValue) {
            $escapedValue = CommandUtility::escapeShellArgument(trim($unescapedValue, '"\''));
            $commandOptionsString .= sprintf(' -D%s=%s', $property, $escapedValue);
        }

        return $commandOptionsString;
    }
}
