<?php
//© 2022 Martin Peter Madsen
namespace MTM\Utilities\Models\Processes\BashShell;

abstract class Initialize extends Commands
{
	protected $_procObj=null;
	
	protected function getProc()
	{
		if ($this->_procObj === null) {
			$path	= \MTM\Utilities\Factories::getSoftware()->getOsTool()->getExecutablePath("bash");
			if ($path === false) {
				throw new \Exception("Executable for bash does not exist");
			}
			$this->_procObj = \MTM\Utilities\Factories::getProcesses()->getProcOpen($path);
		}
		return $this->_procObj;
	}
}