<?php
//© 2019 Martin Peter Madsen
namespace MTM\Utilities\Factories;

class Processes
{	
	//USE: $procObj		= \MTM\Utilities\Factories::getProcesses()->__METHOD__();
	protected $_s=array();
	protected $_asyncMethod="callback";
	
	public function setAsyncMethod($str)
	{
		if (in_array($str, array("callback", "eventloop")) === false) {
			throw new \Exception("Invalid async method: ".$str, 3563);
		}
		$this->_asyncMethod		= $str;
		return $this;
	}
	public function getAsyncMethod()
	{
		return $this->_asyncMethod;
	}
	public function getProcOpen($strCmd=null)
	{
		$rObj	= new \MTM\Utilities\Models\Processes\ProcOpen();
		if ($strCmd !== null) {
			$rObj->setCommand($strCmd);
		}
		return $rObj;
	}
	public function getBashShell($shared=true)
	{
		if ($shared === true) {
			if (array_key_exists(__FUNCTION__, $this->_s) === false) {
				$this->_s[__FUNCTION__]	= new \MTM\Utilities\Models\Processes\BashShell\Zstance();
			}
			return $this->_s[__FUNCTION__];
		} else {
			return new \MTM\Utilities\Models\Processes\BashShell\Zstance();
		}
	}
	public function getEventLoop()
	{
		if (array_key_exists(__FUNCTION__, $this->_s) === false) {
			$this->_s[__FUNCTION__]	= new \MTM\Utilities\Tools\EventLoop\Loop();
		}
		return $this->_s[__FUNCTION__];
	}
	public function getCallBacks()
	{
		if (array_key_exists(__FUNCTION__, $this->_s) === false) {
			$this->_s[__FUNCTION__]	= new \MTM\Utilities\Tools\CallBacks\Process();
		}
		return $this->_s[__FUNCTION__];
	}
}