<?php
//© 2022 Martin Peter Madsen
namespace MTM\Utilities\Tools\EventLoop;

class Event
{
	protected $_guid=null;
	protected $_cbObj=array();
	protected $_cbMethod=false;
	protected $_active=false;
	protected $_nextRun=0;

	public function __construct($obj, $method)
	{
		if (is_object($obj) === false) {
			throw new \Exception("Invalid input, object expected", 15671);
		} elseif (is_string($method) === false) {
			throw new \Exception("Invalid input, string expected", 15672);
		} elseif (method_exists($obj, $method) === false) {
			throw new \Exception("Invalid input, object does not contain method", 15673);
		}
		$this->_guid		= \MTM\Utilities\Factories::getGuids()->getV4()->get(false);
		$this->_cbObj		= $obj;
		$this->_cbMethod	= $method;
		$this->_active		= true;
		$this->_nextRun		= (time() - 1);
	}
	public function getGuid()
	{
		return $this->_guid;
	}
	public function getCbObj()
	{
		return $this->_cbObj;
	}
	public function getCbMethod()
	{
		return $this->_cbMethod;
	}
	public function isActive()
	{
		return $this->_active;
	}
	public function getNextRun()
	{
		return $this->_nextRun;
	}
	public function setNextRun($miliEpoch)
	{
		$this->_nextRun		= $miliEpoch;
		return $this;
	}
	public function setNextRunDelay($miliSec)
	{
		$this->setNextRun(\MTM\Utilities\Factories::getTime()->getMicroEpoch() + ($miliSec / 1000));
		return $this;
	}
	public function execute()
	{
		//let exceptions through
		call_user_func_array(array($this->getCbObj(), $this->getCbMethod()), array($this));
		return $this;
	}
	public function terminate()
	{
		$this->_active	= false;
		\MTM\Utilities\Factories::getProcesses()->getEventLoop()->removeEvent($this);
		return $this;
	}
}