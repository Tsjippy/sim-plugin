<?php
//© 2019 Martin Madsen
namespace MTM\FS\Factories;

class Directories extends Base
{
	//USE: $dirObj	= \MTM\FS\Factories::getDirectories()->getDirectory("/tmp/");
	
	public function __construct()
	{
		$this->_cStore["baseTmpDir"]	= null;
		$this->_cStore["tmpDirs"]		= array();
		register_shutdown_function(array($this, '__destruct'));
	}
	public function __destruct()
	{
		if (count($this->_cStore["tmpDirs"]) > 0) {
			$dirObjs					= $this->_cStore["tmpDirs"];
			$this->_cStore["tmpDirs"]	= array();
			foreach ($dirObjs as $id => $dirObj) {
				$dirObj->delete();
				unset($this->_cStore["tmpDirs"][$id]);
			}
		}
	}	
	public function getDirectory($path=null)
	{
		$dirObj	= new \MTM\FS\Models\Directory();
		$dirObj->setTool($this->getLocalDirectoriesTool());
		
		if ($path != "") {
			$pDir	= $dirObj;
			$dirs	= $this->getSplitPath($path);
			foreach ($dirs as $dir) {
				$dirObj	= new \MTM\FS\Models\Directory();
				$dirObj->setTool($this->getLocalDirectoriesTool());
				
				$dirObj->setName($dir);
				if ($pDir !== null) {
					$dirObj->setParent($pDir);
					$pDir->addChild($dirObj);
				}
				$pDir	= $dirObj;
			}
		}
		return $dirObj;
	}
	public function getDirectoryFromFilePath($filePath)
	{
		if (is_string($filePath) === true) {
			
			$parts		= $this->getSplitPath($filePath);
			//last element must be the filename
			array_pop($parts);
			
			if (DIRECTORY_SEPARATOR == "/") {
				//linux paths start with /
				if (count($parts) > 0) {
					$dirPath	= DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $parts) . DIRECTORY_SEPARATOR;
				} else {
					$dirPath	= DIRECTORY_SEPARATOR;
				}
				
			} else {
				//windows, the first element is the drive letter + ":"
				$dirPath	= implode(DIRECTORY_SEPARATOR, $dirs) . DIRECTORY_SEPARATOR;
			}
			
			return $this->getDirectory($dirPath);
			
		} else {
			throw new \Exception("Invalid Input");
		}
	}
	public function cloneDirectory($dirObj)
	{
		$rDirObj	= $this->getDirectory($dirObj->getPathAsString());
		$rDirObj->setTool($dirObj->getTool());
		return $rDirObj;
	}
	public function getLocalDirectoriesTool()
	{
		if (array_key_exists(__FUNCTION__, $this->_cStore) === false) {
			$this->_cStore[__FUNCTION__]	= new  \MTM\FS\Tools\Directories\Local();
		}
		return $this->_cStore[__FUNCTION__];
	}
	public function getTempDirectory($baseDir=null)
	{
		//this directory and all content is deleted when php terminates
		if ($baseDir === null) {
			if ($this->_cStore["baseTmpDir"] === null) {
				$path		= MTM_FS_TEMP_PATH . uniqid("MTM-Temp-");
				$baseTmpDir	= $this->getDirectory($path);
				$this->_cStore["baseTmpDir"]	= $baseTmpDir;
				$this->setAsTempDir($baseTmpDir);
			}
			$baseDir	= $this->_cStore["baseTmpDir"];
		} elseif (is_string($baseDir) === true) {
			$baseDir	= $this->getDirectory($baseDir);
		}

		$sPath	= $baseDir->getPathAsString();
		while (true) {
			$tmpPath		= $sPath . DIRECTORY_SEPARATOR . uniqid();
			$tmpDir			= $this->getDirectory($tmpPath);
			if ($tmpDir->getExists() === false) {
				$tmpDir->create();
				$this->setAsTempDir($tmpDir);
				return $tmpDir;
			}
		}
	}
	public function setAsTempDir($dirObj)
	{
		$this->_cStore["tmpDirs"][]   = $dirObj;
		return $this;
	}
	public function getNonTempDirectory($baseDir=null)
	{
		//this directory will not be deleted at the end of the session
		//however it will be removed on reboot.
		//we simply find an available name and create it
		if ($baseDir === null) {
			$baseDir	= $this->getDirectory(MTM_FS_TEMP_PATH);
		} elseif (is_string($baseDir) === true) {
			$baseDir	= $this->getDirectory($baseDir);
		}
		$sPath	= $baseDir->getPathAsString();
		while (true) {
			$newPath		= $sPath . DIRECTORY_SEPARATOR . uniqid();
			$newDir			= $this->getDirectory($newPath);
			if ($newDir->getExists() === false) {
				$newDir->create();
				return $newDir;
			}
		}
	}
}