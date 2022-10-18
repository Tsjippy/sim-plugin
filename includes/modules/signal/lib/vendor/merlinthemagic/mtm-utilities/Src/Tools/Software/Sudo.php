<?php
//© 2019 Martin Peter Madsen
namespace MTM\Utilities\Tools\Software;

class Sudo extends \MTM\Utilities\Tools\Base
{
	public function isEnabled($name)
	{
		$hId	= hash("sha256", __FUNCTION__ . $name);
		if (array_key_exists($hId, $this->_cStore) === false) {
			if ($this->getOs()->getType() == "linux") {
				
				//first check that sudo is installed
				$sudoPath	= $this->getOs()->getExecutablePath("sudo");
				if ($sudoPath === false) {
					return false;
				} else {
					//try the generic --help to determine if the app will respond through sudo
					$strCmd		= $sudoPath . " " . $name . " --help";
					$data		= trim($this->exeCmd($strCmd, false));
					if (strlen($data) > 0) {
						$this->_cStore[$hId]	= true;
					} else {
						$this->_cStore[$hId]	= false;
					}
				}
				
			} else {
				throw new \Exception("Not handled");
			}
		}
		return $this->_cStore[$hId];
	}
}