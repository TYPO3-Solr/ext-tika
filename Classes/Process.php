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

namespace ApacheSolrForTypo3\Tika;

/**
 * Run, check, and stop external processes. Linux only.
 */
class Process
{
    /**
     * Process ID
     */
    protected ?int $pid = null;

    /**
     * Executable running the command
     */
    protected string $executable;

    /**
     * Executable arguments
     */
    protected string $arguments;

    public function __construct(string $executable, string $arguments = '')
    {
        $this->executable = $executable;
        $this->arguments = $arguments;
    }

    /**
     * Arguments getter
     */
    public function getArguments(): string
    {
        return $this->arguments;
    }

    /**
     * Arguments setter
     */
    public function setArguments(string $arguments): void
    {
        $this->arguments = $arguments;
    }

    /**
     * Gets the process executable
     */
    public function getExecutable(): string
    {
        return $this->executable;
    }

    /**
     * Gets the process ID
     *
     * @return int|null process ID
     */
    public function getPid(): ?int
    {
        return $this->pid;
    }

    /**
     * Sets the process ID
     */
    public function setPid(int $pid): void
    {
        $this->pid = $pid;
    }

    /**
     * Tries to find the process' pid using ps
     *
     * @return int|null Null if the pid can't be found, otherwise the pid
     */
    public function findPid(): ?int
    {
        $processCommand = $this->executable;
        if (!empty($this->arguments)) {
            $processCommand .= ' ' . $this->arguments;
        }

        $ps = 'ps h --format pid,args -C ' . basename($this->executable);
        $output = [];
        exec($ps, $output);

        foreach ($output as $line) {
            [$pid, $command] = explode(' ', trim($line), 2);
            $command = $this->escapePsOutputCommand($command);
            if ($command == $processCommand) {
                return (int)$pid;
            }
        }

        return null;
    }

    /**
     * Escapes 'ps' command output to match what we expect to get as arguments
     * when executing a command.
     */
    protected function escapePsOutputCommand(string $command): string
    {
        $command = explode(' ', $command);

        foreach ($command as $k => $v) {
            if ($k == 0) {
                // skip the executable
                continue;
            }

            if ($v[0] != '-') {
                $command[$k] = escapeshellarg($v);
            }
        }

        return implode(' ', $command);
    }

    /**
     * Starts the process.
     *
     * @return bool TRUE if the process could be started, FALSE otherwise
     */
    public function start(): bool
    {
        $this->runCommand();
        return $this->isRunning();
    }

    /**
     * Executes the command
     */
    protected function runCommand(): void
    {
        $command = 'nohup ' . $this->executable;
        if (!empty($this->arguments)) {
            $command .= ' ' . $this->arguments;
        }
        $command .= ' > /dev/null 2>&1 & echo $!';

        $output = [];
        exec($command, $output);

        $this->pid = (int)$output[0];
    }

    /**
     * Checks whether the process is running
     *
     * @return bool TRUE if the process is running, FALSE otherwise
     */
    public function isRunning(): bool
    {
        if (is_null($this->pid)) {
            return false;
        }

        $running = false;
        $output = [];

        $command = 'ps h -p ' . $this->pid;
        exec($command, $output);

        if (!empty($output)) {
            $running = true;
        }

        return $running;
    }

    /**
     * Stops the process
     */
    public function stop(): bool
    {
        $command = 'kill ' . $this->pid;
        exec($command);

        return !$this->isRunning();
    }
}
