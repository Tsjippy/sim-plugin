<?php
//© 2019 Martin Peter Madsen
namespace MTM\Utilities\Tools;

class Base
{
	protected $_cStore=array();
	
	protected function getOs()
	{
		return \MTM\Utilities\Factories::getSoftware()->getOsTool();
	}
	protected function getTime()
	{
		return \MTM\Utilities\Factories::getTime()->getMicroEpoch();
	}
	protected function exeCmd($strCmd, $throw=true)
	{
		exec($strCmd, $rData, $status);
		if ($status == 0) {
			return implode("\n", $rData);
		} else {
			if ($throw === true) {
				throw new \Exception("Shell execution error: " . $status);
			} else {
				return false;
			}
		}
	}
}