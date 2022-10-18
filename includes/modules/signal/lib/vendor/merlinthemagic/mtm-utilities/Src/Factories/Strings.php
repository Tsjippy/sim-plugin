<?php
//© 2019 Martin Peter Madsen
namespace MTM\Utilities\Factories;

class Strings
{	
	//USE: $utilObj		= \MTM\Utilities\Factories::getStrings()->__METHOD__();
	
	protected $_s=array();

	public function getRandomByRegex($len, $regex="A-Za-z0-9\#\-\+\:\;\.\,\!")
	{
		return $this->getRandom()->getByRegex($len, $regex);
	}
	public function getRandom()
	{
		if (array_key_exists(__FUNCTION__, $this->_s) === false) {
			$this->_s[__FUNCTION__]	= new \MTM\Utilities\Models\Strings\Random();
		}
		return $this->_s[__FUNCTION__];
	}
	public function getHashing()
	{
		if (array_key_exists(__FUNCTION__, $this->_s) === false) {
			$this->_s[__FUNCTION__]	= new \MTM\Utilities\Models\Strings\Hashing();
		}
		return $this->_s[__FUNCTION__];
	}
	public function getEmail()
	{
		if (array_key_exists(__FUNCTION__, $this->_s) === false) {
			$this->_s[__FUNCTION__]	= new \MTM\Utilities\Tools\Strings\Email();
		}
		return $this->_s[__FUNCTION__];
	}
	public function getSHA()
	{
		//depricate 
		return \MTM\Utilities\Factories::getHashing()->getSHA();
	}
	public function getBytes()
	{
		if (array_key_exists(__FUNCTION__, $this->_s) === false) {
			$this->_s[__FUNCTION__]	= new \MTM\Utilities\Tools\Strings\Bytes();
		}
		return $this->_s[__FUNCTION__];
	}
	public function getEscape()
	{
		if (array_key_exists(__FUNCTION__, $this->_s) === false) {
			$this->_s[__FUNCTION__]	= new \MTM\Utilities\Tools\Strings\Escape();
		}
		return $this->_s[__FUNCTION__];
	}
	public function getASCII()
	{
		if (array_key_exists(__FUNCTION__, $this->_s) === false) {
			$this->_s[__FUNCTION__]	= new \MTM\Utilities\Tools\Strings\ASCII();
		}
		return $this->_s[__FUNCTION__];
	}
}