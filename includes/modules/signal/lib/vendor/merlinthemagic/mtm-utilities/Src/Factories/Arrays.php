<?php
//© 2019 Martin Peter Madsen
namespace MTM\Utilities\Factories;

class Arrays
{	
	//USE: $utilObj		= \MTM\Utilities\Factories::getArrays()->__METHOD__();
	
	protected $_cStore=array();

	public function getDifferential()
	{
		if (array_key_exists(__FUNCTION__, $this->_cStore) === false) {
			$this->_cStore[__FUNCTION__]	= new \MTM\Utilities\Tools\Arrays\Differential();
		}
		return $this->_cStore[__FUNCTION__];
	}
}