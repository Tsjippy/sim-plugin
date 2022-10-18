<?php
//© 2019 Martin Peter Madsen
namespace MTM\Utilities\Factories;

class Software
{	
	//USE: $toolObj		= \MTM\Utilities\Factories::getSoftware()->__METHOD__();
	
	protected $_cStore=array();

	public function getOsTool()
	{
		if (array_key_exists(__FUNCTION__, $this->_cStore) === false) {
			$this->_cStore[__FUNCTION__]	= new \MTM\Utilities\Tools\Software\OperatingSystem();
		}
		return $this->_cStore[__FUNCTION__];
	}
	public function getSudoTool()
	{
		if (array_key_exists(__FUNCTION__, $this->_cStore) === false) {
			$this->_cStore[__FUNCTION__]	= new \MTM\Utilities\Tools\Software\Sudo();
		}
		return $this->_cStore[__FUNCTION__];
	}
	public function getPhpTool()
	{
		if (array_key_exists(__FUNCTION__, $this->_cStore) === false) {
			$this->_cStore[__FUNCTION__]	= new \MTM\Utilities\Tools\Software\Php();
		}
		return $this->_cStore[__FUNCTION__];
	}
	public function getShellTool()
	{
		if (array_key_exists(__FUNCTION__, $this->_cStore) === false) {
			$this->_cStore[__FUNCTION__]	= new \MTM\Utilities\Tools\Software\Shells();
		}
		return $this->_cStore[__FUNCTION__];
	}
}