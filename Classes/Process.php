<?php
namespace ApacheSolrForTypo3\Tika;

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



/**
 * Run, check, and stop external processes.
 * Linux only. (Windows does not work).
 *
 * @package ApacheSolrForTypo3\Tika
 */
class Process {

	/**
	 * Process ID
	 *
	 * @var integer|NULL
	 */
	protected $pid = NULL;

	/**
	 * Executable running the command
	 *
	 * @var string
	 */
	protected $executable;

	/**
	 * Executable arguments
	 *
	 * @var string
	 */
	protected $arguments;


	/**
	 * Constructor
	 *
	 * @param string $executable
	 * @param string $arguments
	 */
	public function __construct($executable, $arguments = '') {
		$this->executable = $executable;
		$this->arguments  = $arguments;
	}

	/**
	 * Arguments setter
	 *
	 * @param $arguments
	 */
	public function setArguments($arguments) {
		$this->arguments = $arguments;
	}

	/**
	 * Arguments getter
	 *
	 * @return string
	 */
	public function getArguments() {
		return$this->arguments;
	}

	/**
	 * Gets the process executable
	 *
	 * @return string
	 */
	public function getExecutable() {
		return $this->executable;
	}

	/**
	 * Executes the command
	 *
	 * @return void
	 */
	protected function runCommand() {
		$command = 'nohup ' . $this->executable . ' ' . $this->arguments . ' > /dev/null 2>&1 & echo $!';
		$output = array();

		exec($command, $output);

		$this->pid = (int) $output[0];
	}

	/**
	 * Sets the process ID
	 *
	 * @param integer $pid
	 * @return void
	 */
	public function setPid($pid) {
		$this->pid = (int) $pid;
	}

	/**
	 * Gets the process ID
	 *
	 * @return int process ID
	 */
	public function getPid() {
		return $this->pid;
	}

	/**
	 * Tries to find the process' pid using ps
	 *
	 * @return int|null Null if the pid can't be found, otherwise the pid
	 */
	public function findPid() {
		if (empty($this->arguments)) {
			throw new \RuntimeException('No command given');
		}

		$processCommand = $this->executable . ' ' . $this->arguments;
		$ps = 'ps h --format pid,args -C ' . basename($this->executable);
		$output = array();
		exec($ps, $output);

		foreach ($output as $line) {
			list($pid, $command) = explode(' ', trim($line), 2);
			$command = $this->escapePsOutputCommand($command);
			if ($command == $processCommand) {
				return (int)$pid;
			}
		}

		return null;
	}

	/**
	 * Checks whether the process is running
	 *
	 * @return bool TRUE if the process is running, FALSE otherwise
	 */
	public function isRunning() {
		if (is_null($this->pid)) {
			return FALSE;
		}

		$running = FALSE;
		$output  = array();

		$command = 'ps h -p ' . $this->pid;
		exec($command, $output);

		if (!empty($output)) {
			$running = TRUE;
		}

		return $running;
	}

	/**
	 * Starts the process.
	 *
	 * @return bool TRUE if the process could be started, FALSE otherwise
	 */
	public function start() {
		$status = FALSE;

		if ($this->arguments != '') {
			$this->runCommand();
			$status = $this->isRunning();
		}

		return $status;
	}

	/**
	 * Stops the process
	 *
	 * @return bool
	 */
	public function stop() {
		$stopped = NULL;

		$command = 'kill ' . $this->pid;
		exec($command);

		if ($this->isRunning() == FALSE) {
			$stopped = TRUE;
		} else {
			$stopped = FALSE;
		}

		return $stopped;
	}

	/**
	 * Escapes 'ps' command output to match what we expect to get as arguments
	 * when executing a command.
	 *
	 * @param $command
	 * @return string
	 */
	protected function escapePsOutputCommand($command) {
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
}
