<?php
//© 2019 Martin Peter Madsen
namespace MTM\Utilities\Models\Processes;

class ProcOpen
{
	protected $_isInit=false;
	protected $_isTerm=false;
	protected $_exitCode=null;
	protected $_strCmd=null;
	protected $_procRes=null;
	protected $_readPipe=null;
	protected $_writePipe=null;
	protected $_errorPipe=null;
	
	public function __destruct()
	{
		$this->terminate();
	}
	public function write($strCmd)
	{
		if ($this->_isTerm === false) {
			$this->initialize();
			
			$strCmd	.= PHP_EOL;
			$len	= strlen($strCmd);
			$bytes	= fwrite($this->_writePipe, $strCmd);
			if ($len !== $bytes) {
				throw new \Exception("Write failed. Wrote: " . $bytes . " of " . $len);
			}
			return $this;
			
		} else {
			throw new \Exception("Cannot write, process is terminated");
		}
	}
	public function read($timeout=2500, $returnOnEmpty=true)
	{
		//set timeouts on your program executing if possible and set this timeout higher
		//we get returns that include errors, but only if the program has exited
		//if you dont wait long enough you simply get a empty data and error == "Timeout"
		
		if ($this->_isTerm === false) {
			$this->initialize();
			
			if (is_bool($returnOnEmpty) === false) {
				throw new \Exception("invalid returnOnEmpty input");
			} elseif (is_int($timeout) === false) {
				throw new \Exception("invalid timeout input");
			}
			
			$tTime			= \MTM\Utilities\Factories::getTime()->getMicroEpoch() + ($timeout / 1000);
			$rObj			= new \stdClass();
			$rObj->data		= null;
			$rObj->error	= null;

			while (true) {
				
				$recv	= false;
				$eData	= fread($this->_errorPipe, 1024);
				if (strlen($eData) > 0) {
					$rObj->error	.= $eData;
					$recv			= true;
				}

				$nData	= fread($this->_readPipe, 1024);
				if ($nData != "") {
					$rObj->data		.= $nData;
					$recv			= true;
				}
				
				if($tTime < \MTM\Utilities\Factories::getTime()->getMicroEpoch()) {
					$rObj->error	.= "Timeout";
					return $rObj;
				} elseif ($recv === false) {
					if ($returnOnEmpty === true && $rObj->data !== null) {
						//we recived some data and then it stopped
						return $rObj;
						
					} else {
						usleep(10000);
					}
				}
			}

		} else {
			throw new \Exception("Cannot read, process is terminated");
		}
	}
	public function readRegEx($regEx, $timeout=10000)
	{
		//reads until regEx is found or timeout is reached
		
		if ($this->_isTerm === false) {
			$this->initialize();
			
			if (is_string($regEx) === false) {
				throw new \Exception("invalid regex input");
			} elseif (is_int($timeout) === false) {
				throw new \Exception("invalid timeout input");
			}
			
			$tTime			= \MTM\Utilities\Factories::getTime()->getMicroEpoch() + ($timeout / 1000);
			$rObj			= new \stdClass();
			$rObj->data		= null;
			$rObj->error	= null;
			
			while (true) {
				
				$recv	= false;
				$eData	= fread($this->_errorPipe, 1024);
				if (strlen($eData) > 0) {
					$rObj->error	.= $eData;
					$recv			= true;
				}
				
				$nData	= fread($this->_readPipe, 1024);
				if ($nData != "") {
					$rObj->data		.= $nData;
					$recv			= true;
				}
				
				if($tTime < \MTM\Utilities\Factories::getTime()->getMicroEpoch()) {
					$rObj->error	.= "Timeout";
					return $rObj;
				} elseif ($recv === true && preg_match("/".$regEx."/s", $rObj->data) === 1) {
					return $rObj;
				} else {
					usleep(10000);
				}
			}
			
		} else {
			throw new \Exception("Cannot read, process is terminated");
		}
	}
	public function setCommand($strCmd)
	{
		$this->_strCmd	= $strCmd;
		return $this;
	}
	public function getCommand()
	{
		return $this->_strCmd;
	}
	public function getExitCode()
	{
		//only available after termination
		return $this->_exitCode;
	}
	protected function initialize()
	{
		if ($this->_isInit === false) {

			if ($this->getCommand() === null) {
				throw new \Exception("Missing command");
			}
			$structs = array(
					0 => array("pipe", "r"),
					1 => array("pipe", "w"),
					2 => array("pipe", "w")
			);
			
			$cwd	= sys_get_temp_dir();
			$env	= array();
			$proc	= proc_open($this->getCommand(), $structs, $pipes, $cwd, $env);
			
			if (is_resource($proc) === true) {
				
				$this->_procRes		= $proc;
				$this->_writePipe	= $pipes[0];
				$this->_readPipe	= $pipes[1];
				$this->_errorPipe	= $pipes[2];
				
				stream_set_blocking($this->_writePipe, false);
				stream_set_blocking($this->_readPipe, false);
				stream_set_blocking($this->_errorPipe, false);

				$this->_isInit		= true;
				
			} else {
				throw new \Exception("Failed to stat process");
			}
		}
	}
	public function terminate()
	{
		if ($this->_isTerm === false) {
			$this->_isTerm	= true;
			
			if (is_resource($this->_writePipe) === true) {
				fclose($this->_writePipe);
				$this->_writePipe	= null;
			}
			if (is_resource($this->_readPipe) === true) {
				fclose($this->_readPipe);
				$this->_readPipe	= null;
			}
			if (is_resource($this->_errorPipe) === true) {
				fclose($this->_errorPipe);
				$this->_errorPipe	= null;
			}
			if (is_resource($this->_procRes) === true) {
				$this->_exitCode	= proc_close($this->_procRes);
				$this->_procRes		= null;
			}
		}
	}
}