<?php
//© 2019 Martin Madsen
namespace MTM\FS\Factories;

class Files extends Base
{
	//USE: $fileObj	= \MTM\FS\Factories::getFiles()->getFile("file.txt", "/tmp/");

	public function __construct()
	{
	    $this->_cStore["tmpFiles"]	= array();
		register_shutdown_function(array($this, '__destruct'));
	}
	public function __destruct()
	{
		if (count($this->_cStore["tmpFiles"]) > 0) {
			$fileObjs					= $this->_cStore["tmpFiles"];
			$this->_cStore["tmpFiles"]	= array();
			foreach ($fileObjs as $id => $fileObj) {
				$fileObj->delete();
				unset($this->_cStore["tmpFiles"][$id]);
			}
		}
	}
	public function getLockFile($name, $maxAge=30)
	{
		$fileObj	= new \MTM\FS\Models\LockFile();
		$fileObj->setTool($this->getLocalFileTool());
		$fileObj->setName($name);
		
		$path		= \MTM\FS\Factories::getDirectories()->getDirectory(MTM_FS_TEMP_PATH . "lockFiles");
		$fileObj->setDirectory($path);
		
		$fileObj->setMetric("maxAge", $maxAge);

		return $fileObj;
	}
	public function getFile($name=null, $path=null)
	{
		$fileObj	= new \MTM\FS\Models\File();
		$fileObj->setTool($this->getLocalFileTool());

		if ($name !== null) {
			$fileObj->setName($name);
		}
		if ($path !== null) {
			if (is_object($path) === false) {
				$path	= \MTM\FS\Factories::getDirectories()->getDirectory($path);
			}
			$fileObj->setDirectory($path);
		}
		return $fileObj;
	}
	public function getLinkedFile($name=null, $path=null)
	{
		//this will resolve a file that is a link and return the
		//real file location
		$fileObj	= $this->getFile($name, $path);
		$type		= $fileObj->getType();
		if ($type == "link") {
			return $this->getFileFromPath(readlink($fileObj->getPathAsString()));
		} elseif ($type == "file") {
			return $fileObj;
		} else {
			throw new \Exception("Cannot handle type: " . $type);
		}
	}
	public function getTempFile($extension=null, $baseDir=null)
	{
	    //these files are deleted when the process destructs
	    if ($extension === null) {
	        $extension	= "file";
	    }
	    if ($baseDir === null) {
	    	$baseDir	= \MTM\FS\Factories::getDirectories()->getTempDirectory();
	    } elseif (is_string($baseDir) === true) {
	    	$baseDir	= \MTM\FS\Factories::getDirectories()->getDirectory($baseDir);
	    } elseif ($baseDir instanceof \MTM\FS\Models\Directory === false) {
	    	throw new \Exception("Invalid directory input");
	    }
	    
	    if ($baseDir->getWritable() === false) {
	    	throw new \Exception("Cannot generate temp file in a directory that is not writable");
	    }
	    $sPath	= $baseDir->getPathAsString();
	    $tTime  = time() + 5;
	    while (true) {
	        $filename	= uniqid() . "." . $extension;
	        $fileObj	= $this->getFile($filename, $sPath);
	        if ($fileObj->getExists() === false) {
	            $fileObj->create();
	            $this->setAsTempFile($fileObj);
	            return $fileObj;
	        } elseif($tTime < time()) {
	            throw new \Exception("Failed to generate a temp file");
	        }
	    }
	}
	public function setAsTempFile($fileObj)
	{
		$this->_cStore["tmpFiles"][]   = $fileObj;
		return $this;
	}
	public function getFileFromPath($filePath)
	{
		if (is_string($filePath) === true) {
			
			$dirObj		= \MTM\FS\Factories::getDirectories()->getDirectoryFromFilePath($filePath);
			$parts		= $this->getSplitPath($filePath);
			//last element must be the filename
			$fileName	= array_pop($parts);
			return $this->getFile($fileName, $dirObj->getPathAsString());
		} else {
			throw new \Exception("Invalid Input");
		}
	}
	public function cloneFile($fileObj)
	{
		$rFileObj	= $this->getFile($fileObj->getName(), $fileObj->getDirectory()->getPathAsString());
		$rFileObj->setTool($fileObj->getTool());
		return $rFileObj;
	}
	public function getLocalFileTool()
	{
		if (array_key_exists(__FUNCTION__, $this->_cStore) === false) {
			$this->_cStore[__FUNCTION__]	= new \MTM\FS\Tools\Files\Local();
		}
		return $this->_cStore[__FUNCTION__];
	}
}