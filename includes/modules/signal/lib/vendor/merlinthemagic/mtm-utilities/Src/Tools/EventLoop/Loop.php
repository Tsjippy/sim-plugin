<?php
//© 2022 Martin Peter Madsen
namespace MTM\Utilities\Tools\EventLoop;

class Loop
{
	protected $_eventObjs=array();
	protected $_status=false;
	protected $_nextRun=0;
	protected $_defaultInterval=1000; //default run time advance unless the event is modified at the target
	
	public function addEvent($obj, $method)
	{
		$eObj	= null;
		foreach ($this->_eventObjs as $evObj) {
			if ($evObj->getCbObj() === $obj && $evObj->getCbMethod() === $method) {
				$eObj	= $evObj;
				break;
			}
		}
		if ($eObj === null) {
			$eObj					= new \MTM\Utilities\Tools\EventLoop\Event($obj, $method);
			$this->_eventObjs[]		= $eObj;
		}
		$this->_status	= true;
		return $eObj;
	}
	public function removeEvent($eObj)
	{
		foreach ($this->_eventObjs as $index => $evObj) {
			if ($evObj->getGuid() === $eObj->getGuid()) {
				unset($this->_eventObjs[$index]);
				if (count($this->_eventObjs) === 0) {
					$this->_status	= false;
				}
				break;
			}
		}
		return $this;
	}
	public function runLoop($runTime=-1)
	{
		if (is_int($runTime) === false) {
			throw new \Exception("Invalid input", 15669);
		} elseif ($this->_status === false) {
			throw new \Exception("Need at least one event call back", 15670);
		}
		$tFact			= \MTM\Utilities\Factories::getTime();
		if ($runTime < 0) {
			while($this->_status === true) {
				$this->runOnce();
				$sleep	= intval(($this->_nextRun - $tFact->getMicroEpoch()) * 1000000) - 1000;
				if ($sleep > 0) {
					usleep($sleep);
				}
			}
		} else {
			$cTime		= time();
			$tTime		= $cTime + $runTime;
			while($this->_status === true) {
				$this->runOnce();
				if ($tTime >= time()) {
					$this->_status	= false;
				} else {
					$sleep	= intval(($this->_nextRun - $tFact->getMicroEpoch()) * 1000000) - 1000;
					if ($sleep > 0) {
						usleep($sleep);
					}
				}
			}
		}
	}
	public function runOnce()
	{
		$tFact			= \MTM\Utilities\Factories::getTime();
		$this->_nextRun	= $tFact->getMicroEpoch() + 60;
		foreach ($this->_eventObjs as $evObj) {
			if ($evObj->isActive() === true && $evObj->getNextRun() <= $tFact->getMicroEpoch()) {
				//set a default poll delay, the call back should modify the eventObj if a different delay is desired
				$evObj->setNextRunDelay($this->_defaultInterval)->execute();
			}
			if ($this->_nextRun > $evObj->getNextRun()) {
				$this->_nextRun		= $evObj->getNextRun();
			}
		}
	}
	public function getStatus()
	{
		return $this->_status;
	}
	public function terminate()
	{
		$this->_status	= false;
		return $this;
	}
}