<?php
//© 2019 Martin Madsen
namespace MTM\FS\Models;

class Directory
{
	protected $_name=null;
	protected $_parent=null;
	protected $_childObjs=array();
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
	public function setParent($dirObj)
	{
		$this->_parent	= $dirObj;
	}
	public function getParent()
	{
		return $this->_parent;
	}
	public function addChild($obj)
	{
		//can be both files and directories
		if (array_key_exists($obj->getName(), $this->_childObjs) === false) {
			if ($obj instanceof \MTM\FS\Models\Directory) {
				$obj->setParent($this);
			} elseif ($obj instanceof \MTM\FS\Models\File) {
				$obj->setDirectory($this);
			} else {
				throw new \Exception("Not handled for exists: " . get_class($obj));
			}
			$this->_childObjs[$obj->getName()]	= $obj;
		} else {
			throw new \Exception("Cannot add, child name exists: " . $obj->getName());
		}
		return $this;
	}
	public function getChildren()
	{
		return array_values($this->_childObjs);
	}
	public function getChildByName($name)
	{
		if (array_key_exists($name, $this->_childObjs) === true) {
			return $this->_childObjs[$name];
		} else {
			return null;
		}
	}
	public function appendDirectory($name)
	{
		$dirObj	= \MTM\FS\Factories::getDirectories()->getDirectory($name);
		$this->addChild($dirObj);
		return $dirObj;
	}
	public function getPathAsString()
	{
		$strPath	= "";
		$pDirObj	= $this->getParent();
		if ($pDirObj !== null) {
			$strPath	.= $pDirObj->getPathAsString();
		} elseif ($this->getTool()->getDirSep() == "/" && $this->getName() != "") {
			//Linux, no parent and the directory has a name. No name would be root path
			$strPath	.= $this->getTool()->getDirSep();
		}
		$strPath	.= $this->getName() . $this->getTool()->getDirSep();

		return $strPath;
	}
	public function getExists()
	{
		$type	= $this->getType();
		if ($type == "directory") {
			return true;
		} elseif ($type === null) {
			return false;
		} else {
			throw new \Exception("Exists as type: " . $type);
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
		$this->getTool()->create($this);
		return $this;
	}
	public function getFreeBytes()
	{
		return $this->getTool()->getFreeBytes($this);
	}
	public function delete()
	{
		$this->getTool()->delete($this);
		return $this;
	}
	public function setFromSystem($recursive=false)
	{
		//will populate children from the file system
		if ($this->getExists() === true) {
			
			$files	= $this->getFileNames();
			foreach ($files as $file) {
				if ($this->getChildByName($file) === null) {
					$this->addChild(\MTM\FS\Factories::getFiles()->getFile($file));
				}
			}
			
			$dirs	= $this->getDirectoryNames();
			foreach ($dirs as $dir) {
				$dirObj	= $this->getChildByName($dir);
				if ($dirObj === null) {
					$dirObj	= $this->appendDirectory($dir);
				}
				if ($recursive === true) {
					$dirObj->setFromSystem($recursive);
				}
			}
			
			return $this->getChildren();
			
		} else {
			throw new \Exception("Cannot set children, directory does not exist");
		}
	}
	public function getFileNames()
	{
		return $this->getTool()->getFileNames($this);
	}
	public function getDirectoryNames()
	{
		return $this->getTool()->getDirectoryNames($this);
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
		return \MTM\FS\Factories::getDirectories()->cloneDirectory($this);
	}
}