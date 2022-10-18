<?php
//© 2019 Martin Madsen
namespace MTM\FS\Models;

class File
{
	protected $_name=null;
	protected $_directory=null;
	protected $_toolObj=null;

	public function setName($name)
	{
		$this->_name	= $name;
		return $this;
	}
	public function getName()
	{
		return $this->_name;
	}
	public function setDirectory($dirObj)
	{
		$this->_directory	= $dirObj;
		return $this;
	}
	public function getDirectory()
	{
		return $this->_directory;
	}
	public function getPathAsString()
	{
		$strPath	= "";
		$dirObj		= $this->getDirectory();
		if ($dirObj !== null) {
			$strPath	.= $dirObj->getPathAsString();
		} elseif ($this->getTool()->getDirSep() == "/") {
			//Linux and no parent
			$strPath	.= $this->getTool()->getDirSep();
		}
		$strPath	.= $this->getName();

		return $strPath;
	}
	public function getExtension()
	{
		//if a file extension can be accuratly determined return it
		if (preg_match("/\.([^\.]+$)/", $this->getName(), $rExt) == 1) {
			return strtolower($rExt[1]);
		} else {
			return false;
		}
	}
	//tool OPs
	public function getExists()
	{
		$type	= $this->getType();
		if ($type === null) {
			return false;
		} elseif ($type == "directory") {
			throw new \Exception("Exists as type: " . $type);
		} else {
			return true;
		}
	}
	public function getWritable()
	{
		return $this->getTool()->isWritable($this);
	}
	public function getType()
	{
		return $this->getTool()->getType($this);
	}
	public function getOwner()
	{
		return $this->getTool()->getOwner($this);
	}
	public function create()
	{
		return $this->getTool()->create($this);
	}
	public function delete()
	{
		return $this->getTool()->delete($this);
	}
	public function getLineCount()
	{
		return $this->getTool()->getLineCount($this);
	}
	public function getLines($count=null, $sLine=1)
	{
		if ($count === null) {
			$count	= $this->getLineCount(); //get all lines
		}
		return $this->getTool()->getLines($this, $count, $sLine);
	}
	public function getByteCount()
	{
		return $this->getTool()->getByteCount($this);
	}
	public function getBytes($count=null, $sByte=1)
	{
		if ($count === null) {
			$count	= $this->getByteCount(); //get all bytes
		}
		return $this->getTool()->getBytes($this, $count, $sByte);
	}
	public function setContent($data)
	{
		$this->getTool()->setContent($this, $data);
		return $this;
	}
	public function getContent()
	{
		return $this->getBytes($this->getByteCount(), 1);
	}
	public function addContent($data, $type="append")
	{
		$this->getTool()->addContent($this, $data, $type);
		return $this;
	}
	public function setMode($mode)
	{
		$this->getTool()->setMode($this, $mode);
		return $this;
	}
	public function getMode()
	{
		return $this->getTool()->getMode($this);
	}
	public function copy($dstFileObj, $replace=true)
	{
		$this->getTool()->copy($this, $dstFileObj, $replace);
		return $this;
	}
	public function move($dstFileObj, $replace=true)
	{
		$this->getTool()->move($this, $dstFileObj, $replace);
		return $this;
	}
	public function getTool()
	{
		return $this->_toolObj;
	}
	public function setTool($tObj)
	{
		$this->_toolObj	= $tObj;
		return $this;
	}
	public function getClone()
	{
		return \MTM\FS\Factories::getFiles()->cloneFile($this);
	}
}