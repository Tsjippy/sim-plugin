<?php
//© 2019 Martin Madsen
namespace MTM\FS\Models;

class LockFile extends File
{
	private $_metrics=array();
	
	public function setMetric($name, $value)
	{
		$this->_metrics[$name]	= $value;
		return $this;
	}
	public function setLock()
	{
		$lData	= "lockTime===" . time();
		$lData	.= "\nlockPid===" . getmypid();
		foreach ($this->_metrics as $key => $val) {
			$lData	.= "\n" . $key . "===" . $val;
		}
		$this->setContent($lData);
		return $this;
	}
	public function getMetrics()
	{
		$rObj	= new \stdClass();
		if ($this->getExists() === true) {
			$lines	= array_filter(explode("\n", $this->getContent()));
			foreach ($lines as $line) {
				$vals		= explode("===", $line);
				$key		= $vals[0];
				$val		= $vals[1];
				$rObj->$key	= $val;
			}
		}
		return $rObj;
	}
	public function getPid()
	{
	    //return the PID of the process that set the lock
	    //null if no lock is set
	    return $this->getMetric("lockPid");
	}
	public function getTime()
	{
		return $this->getMetric("lockTime");
	}
	public function getMetric($name)
	{
		$mObj	= $this->getMetrics();
		if (property_exists($mObj, $name) === true) {
			return $mObj->$name;
		} else {
			return null;
		}
	}
	public function getMetricMatch($name, $val)
	{
		$mVal	= $this->getMetric($name);
		if ($mVal == $val) {
			return true;
		} else {
			return false;
		}
	}
	public function isActive()
	{
	    $rTime    = $this->getRemainingTime();
	    if ($rTime > 0) {
	        return true;
	    } else {
	        return false;
	    }
	}
	public function isOwner()
	{
		if ($this->getPid() == getmypid()) {
			return true;
		} else {
			return false;
		}
	}
	public function getRemainingTime()
	{
	    //can be used to decide if a process should delete the lock of let a master process 
	    //take over the expired lock. e.g. only delete if we have atleast 3 sec left to avoid race conditions
	    $rTime  = 0;
	    $mObj	= $this->getMetrics();
	    if (
	        property_exists($mObj, "lockTime") === true
	        && property_exists($mObj, "maxAge") === true
        ) {
            
            $rTime  = ($mObj->lockTime + $mObj->maxAge) - time();
            if ($rTime < 0) {
                $rTime  = 0;
            }
        }
        return $rTime;
	}
}