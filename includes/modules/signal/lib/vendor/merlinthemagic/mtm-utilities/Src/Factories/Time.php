<?php
//© 2019 Martin Peter Madsen
namespace MTM\Utilities\Factories;

class Time
{	
	//USE: $floatTime		= \MTM\Utilities\Factories::getTime()->getMicroEpoch();
	protected $_cStore=array();
	
	public function getMicroEpoch($float=true)
	{
		$sTime	= gettimeofday();
		$mEpoch	= ($sTime["sec"] . "." . str_repeat(0, (6 - strlen($sTime["usec"]))) . $sTime["usec"]);
		if ($float === true) {
			return (float) $mEpoch;
		} else {
			//is string but has higher precision and is always a decimal not an int
			return $mEpoch;
		}
	}
	public function getExcelTool()
	{
		if (array_key_exists(__FUNCTION__, $this->_cStore) === false) {
			$this->_cStore[__FUNCTION__]	= new \MTM\Utilities\Tools\Time\Excel();
		}
		return $this->_cStore[__FUNCTION__];
	}
	public function getUnixEpochTool()
	{
		if (array_key_exists(__FUNCTION__, $this->_cStore) === false) {
			$this->_cStore[__FUNCTION__]	= new \MTM\Utilities\Tools\Time\UnixEpoch();
		}
		return $this->_cStore[__FUNCTION__];
	}
}