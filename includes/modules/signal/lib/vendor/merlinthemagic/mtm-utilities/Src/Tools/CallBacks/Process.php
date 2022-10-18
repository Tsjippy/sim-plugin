<?php
//© 2020 Martin Peter Madsen
namespace MTM\Utilities\Tools\CallBacks;

class Process
{
	protected $_loopCbs=array();
	protected $_totalCount=0; //total amount of messages handled
	protected $_lastEvent=0; //last time a call back resulted in an event
	protected $_idleCur=0; //in micro secs
	protected $_idleStep=5;//in micro secs
	protected $_idleMax=100000;//in micro secs
	protected $_idleCount=0;
	protected $_idleStart=500;//how many empty runs before starting to slow down 
	protected $_runStatus=false;
	
	public function addLoopCb($obj, $method)
	{
		//method should return an integer.
		//if anything was processed then 1 else 0
		//this will help this tool slowdown if every callback is idling
		if (is_object($obj) === false) {
			throw new \Exception("Invalid input, object expected");
		} elseif (is_string($method) === false) {
			throw new \Exception("Invalid input, string expected");
		} elseif (method_exists($obj, $method) === false) {
			throw new \Exception("Invalid input, object does not contain method");
		}
		$this->removeLoopCb($obj, $method);
		$cbObj				= new \stdClass();
		$cbObj->active		= true; //avoids running callbacks that have been removed mid exec of runOnce()
		$cbObj->obj			= $obj;
		$cbObj->method		= $method;
		
		$this->_loopCbs[]	= $cbObj;
		$this->resetRunStatus();
		return $this;
	}
	public function removeLoopCb($obj, $method)
	{
		foreach ($this->_loopCbs as $index => $cbObj) {
			if ($cbObj->obj === $obj && $cbObj->method === $method) {
				$cbObj->active		= false;
				unset($this->_loopCbs[$index]);
				$this->resetRunStatus();
				break;
			}
		}
		return $this;
	}
	public function runLoop($runTime=-1)
	{
		if ($this->_runStatus === false) {
			throw new \Exception("Need at least one call back");
		}
		if ($runTime < 0) {
			while($this->_runStatus === true) {
				$this->runOnce();
			}
		} else {
			$timeFact	= \MTM\Utilities\Factories::getTime();
			$cTime		= $timeFact->getMicroEpoch();
			$tTime		= $cTime + $runTime;
			while($this->_runStatus === true && $tTime > $cTime) {
				$this->runOnce();
				$cTime	= $timeFact->getMicroEpoch();
			}
		}
	}
	public function runOnce()
	{
		$cTime		= \MTM\Utilities\Factories::getTime()->getMicroEpoch();
		$count		= 0;
		foreach ($this->_loopCbs as $cbObj) {
			if ($cbObj->active === true) {
				$rData	= call_user_func_array(array($cbObj->obj, $cbObj->method), array());
				if (is_int($rData) === true) {
					$count	+= $rData;
				}
			}
		}

		if ($count > 0) {
			$this->_totalCount	+= $count;
			$this->_lastEvent	= $cTime;
			$this->_idleCur		= 0;
			$this->_idleCount	= 0;
		} elseif (++$this->_idleCount > $this->_idleStart) {
			//slow down we have not received a message in awhile
			if ($this->_idleCur < $this->_idleMax) {
				$this->_idleCur	+= $this->_idleStep;
			}
			usleep($this->_idleCur);
		}
		return $count;
	}
	public function getTotalCount()
	{
		return $this->_totalCount;
	}
	public function terminate($throw=false)
	{
		$this->_runStatus	= false;
		return $this;
	}
	protected function resetRunStatus()
	{
		if (count($this->_loopCbs) > 0) {
			$this->_runStatus		= true;
		} else {
			$this->_runStatus		= false;
		}
	}
}