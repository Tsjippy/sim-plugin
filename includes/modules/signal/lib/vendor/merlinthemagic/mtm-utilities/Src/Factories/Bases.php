<?php
//© 2019 Martin Peter Madsen
namespace MTM\Utilities\Factories;

class Bases
{	
	//USE: $utilObj		= \MTM\Utilities\Factories::getBases()->__METHOD__();
	protected $_s=array();
	
	public function getConvert()
	{
		if (array_key_exists(__FUNCTION__, $this->_s) === false) {
			$this->_s[__FUNCTION__]	= new \MTM\Utilities\Tools\Bases\Convert();
		}
		return $this->_s[__FUNCTION__];
	}
	public function getBase26()
	{
		if (array_key_exists(__FUNCTION__, $this->_s) === false) {
			$this->_s[__FUNCTION__]	= new \MTM\Utilities\Tools\Bases\Base26();
		}
		return $this->_s[__FUNCTION__];
	}
	public function getBase58()
	{
		if (array_key_exists(__FUNCTION__, $this->_s) === false) {
			$this->_s[__FUNCTION__]	= new \MTM\Utilities\Tools\Bases\Base58();
		}
		return $this->_s[__FUNCTION__];
	}
}