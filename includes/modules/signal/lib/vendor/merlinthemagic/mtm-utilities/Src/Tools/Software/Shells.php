<?php
//© 2019 Martin Peter Madsen
namespace MTM\Utilities\Tools\Software;

class Shells extends \MTM\Utilities\Tools\Base
{
	public function getSH()
	{
		$path	= \MTM\Utilities\Factories::getSoftware()->getOsTool()->getExecutablePath("sh");
		if ($path === false) {
			throw new \Exception("Executable for sh does not exist");
		} else {
			return \MTM\Utilities\Factories::getProcesses()->getProcOpen($path);
		}
	}
}