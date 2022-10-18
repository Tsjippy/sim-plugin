<?php
//© 2020 Martin Peter Madsen
namespace MTM\Utilities\Tools\Arrays;

class Differential
{
	protected $_s=array();
	
	public function getOneDimention()
	{
		if (array_key_exists(__FUNCTION__, $this->_s) === false) {
			$this->_s[__FUNCTION__]		= new \MTM\Utilities\Tools\Arrays\Differential\OneDimention();
		}
		return $this->_s[__FUNCTION__];
	}
}