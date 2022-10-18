<?php
//© 2021 Martin Peter Madsen
namespace MTM\Utilities\Factories;

class Hashing
{	
	//USE: $utilObj		= \MTM\Utilities\Factories::getHashing()->__METHOD__();
	
	protected $_s=array();

	public function getSHA()
	{
		if (array_key_exists(__FUNCTION__, $this->_s) === false) {
			$this->_s[__FUNCTION__]	= new \MTM\Utilities\Tools\Hashing\SHA();
		}
		return $this->_s[__FUNCTION__];
	}
	public function getNTLM()
	{
		if (array_key_exists(__FUNCTION__, $this->_s) === false) {
			$this->_s[__FUNCTION__]	= new \MTM\Utilities\Tools\Hashing\NTLM();
		}
		return $this->_s[__FUNCTION__];
	}
}