<?php
//© 2019 Martin Peter Madsen
namespace MTM\Utilities\Tools\Software;

class Php extends \MTM\Utilities\Tools\Base
{
	protected $_bShell=null;
	protected $_iShell=null;
	protected $_memLimit=null;
	
	public function getMemoryLimit($refresh=false)
	{
		if ($this->_memLimit === null || $refresh === true) {
			$this->_memLimit = ini_get("memory_limit");
			if (preg_match('/^(\d+)(.)$/', $this->_memLimit, $raw) === 1) {
				if ($raw[2] == "M") {
					$this->_memLimit = $raw[1] * 1024 * 1024;
				} elseif ($raw[2] == "K") {
					$this->_memLimit = $raw[1] * 1024;
				} elseif ($raw[2] == "G") {
					$this->_memLimit = $raw[1] * 1024 * 1024 * 1024;
				}
			}
		}
		return $this->_memLimit;
	}
	public function getShell()
	{
		if ($this->_bShell === null) {
			//this is a shared 'sh' shell
			$this->_bShell	= \MTM\Utilities\Factories::getSoftware()->getShellTool()->getSH();
		}
		return $this->_bShell;
	}
	public function getInteractiveShell()
	{
		if ($this->_iShell === null) {
			$path			= $this->getExecutablePath(false);
			$this->_iShell	= \MTM\Utilities\Factories::getProcesses()->getProcOpen($path . " -a");
		}
		return $this->_iShell;
	}
	public function getExecutablePath($curOnly=false)
	{
		if (defined("PHP_BINARY") === true) {
			//perfer the current and not what the environment returns
			return PHP_BINARY;
			
		} elseif ($curOnly === false) {
			$path	= \MTM\Utilities\Factories::getSoftware()->getOsTool()->getExecutablePath("php");
			if ($path !== false) {
				return $path;
			}
		}
		throw new \Exception("Unable to determine executable for php");
	}
}