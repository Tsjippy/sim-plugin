<?php
//© 2019 Martin Peter Madsen
namespace MTM\Utilities\Factories;

class Guids
{	
	//USE: $utilObj		= \MTM\Utilities\Factories::getGuids()->__METHOD__();
	
	protected $_cStore=array();
	
	public function getV4()
	{
		if (array_key_exists(__FUNCTION__, $this->_cStore) === false) {
			$this->_cStore[__FUNCTION__]	= new \MTM\Utilities\Tools\Guids\V4();
		}
		return $this->_cStore[__FUNCTION__];
	}
}