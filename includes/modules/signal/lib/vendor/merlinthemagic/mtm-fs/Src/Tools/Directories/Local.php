<?php
//© 2019 Martin Madsen
namespace MTM\FS\Tools\Directories;

class Local
{
	public function isDirectory($dirObj, $throw=false)
	{
		$isDir	= is_dir($dirObj->getPathAsString());
		if ($isDir === true) {
			return true;
		} else {
			if ($throw === false) {
				return false;
			} else {
				throw new \Exception("Directory does not exist: " . $dirObj->getPathAsString());
			}
		}
	}
	public function isWritable($input)
	{
		if (is_object($input) === true) {
			$input = $input->getPathAsString();
		}
		if (is_writable($input) === true) {
			return true;
		} else {
			return false;
		}
	}
	public function getType($input)
	{
		//can we eliminate the exist call? i think get type returns false when dir is not there
		if (is_object($input) === true) {
			$input = $input->getPathAsString();
		}
		$exists	= file_exists($input);
		if ($exists === true) {
			$type	= filetype($input);
			if ($type == "fifo") {
				return "pipe";
			} elseif ($type == "file") {
				return "file";
			} elseif ($type == "dir") {
				return "directory";
			} elseif ($type == "socket") {
				return "socket";
			} elseif ($type == "link") {
				return "link";
			} else {
				//fifo, char, dir, block, link, file, socket and unknown
				throw new \Exception("Unknown Type: " . $type);
			}
			
		} else {
			//null return since the file does not exist
			return null;
		}
	}
	public function getOwner($dirObj)
	{
		$this->isDirectory($dirObj, true);
		
		if ($this->getDirSep() == "/") {
			//do not know how to do this without a shell
			$strCmd		= "stat -c '%U' " . "'".$dirObj->getPathAsString()."'";
			exec($strCmd, $rData, $status);
			if ($status == 0) {
				$ownerName	= trim($rData);
				if ($ownerName != "") {
					return $ownerName;
				} else {
					throw new \Exception("Failed Get Owner for: " . $fileObj->getPathAsString());
				}
			} else {
				throw new \Exception("Get owner cli command failed with status: " . $status);
			}
			
		} else {
			throw new \Exception("Not handled");
		}
	}
	public function create($dirObj)
	{
		$exist	= $this->isDirectory($dirObj, false);
		if ($exist === false) {
			
			$dirs	= array();
			$curObj	= $dirObj;
			$done	= false;
			while ($done === false) {
				
				$dirs[]		= $curObj->getName();
				$curObj		= $curObj->getParent();
				if ($curObj === null) {
					$done	= true;
				}
			}
			
			$dirs	= array_reverse($dirs);
			if ($this->getDirSep() == "/") {
				//linux
				$oPath		= "";
				$lastParent	= $this->getDirSep();
			} else {
				//windows, the first element must be the drive letter + ":"
				$oPath		= array_shift($dirs);
				$lastParent	= $oPath . $this->getDirSep();
			}
			
			//validate everything first
			$cPath		= $oPath;
			$x=0;
			foreach ($dirs as $dir) {
				$x++;
				$cPath	.= $this->getDirSep() . $dir;
				
				//make sure its not a file
				$type	= $this->getType($cPath);
				if ($type === "directory") {
					//nothing to create
					$lastParent	= $cPath;
				} elseif ($type === null) {

					//make sure we can write to the last parent dir
					$canWrite	= $this->isWritable($lastParent);
					if ($canWrite === false) {
						throw new \Exception("Cannot Create: " . $dirObj->getPathAsString(). " cannot write to: " . $lastParent);
					} else {
						//no need to check any further up stream since it does not exist
						break;
					}
					
				} else {
					throw new \Exception("Failed to Create: " . $dirObj->getPathAsString(). ", path: " . $cPath . " is of type: " . $type);
				}
			}
			
			//all good, now create the missing directories
			$cPath	= $oPath;
			foreach ($dirs as $dir) {
				$x++;
				$cPath	.= $this->getDirSep() . $dir;
				$type	= $this->getType($cPath);
				if ($type === null) {
					$valid	= @mkdir($cPath);
					if ($valid === false) {
						throw new \Exception("Failed to create directory: ".$cPath." in path: " . $dirObj->getPathAsString());
					}
				}
			}
		}
	}
	public function getFileNames($dirObj)
	{
		$exist	= $dirObj->getExists();
		if ($exist === false) {
			throw new \Exception("Directory does not exist");
		}
		
		$fileNames	= array();
		$items		= scandir($dirObj->getPathAsString());
		foreach ($items as $item) {
			$item	= trim($item);
			if ($item != "." && $item != "..") {
				$nPath	= $dirObj->getPathAsString() . $item;
				$type	= $this->getType($nPath);
				if ($type != "directory") {
					$fileNames[]	= $item;
				}
			}
		}
		
		return $fileNames;
	}
	public function getDirectoryNames($dirObj)
	{
	    $exist	= $dirObj->getExists();
	    if ($exist === false) {
	        throw new \Exception("Directory does not exist");
	    }
	    
	    $dirNames	= array();
	    $items		= scandir($dirObj->getPathAsString());
	    foreach ($items as $item) {
	        $item	= trim($item);
	        if ($item != "." && $item != "..") {
	            $nPath	= $dirObj->getPathAsString() . $item;
	            $type	= $this->getType($nPath);
	            if ($type == "directory") {
	                $dirNames[]	= $item;
	            }
	        }
	    }
	    
	    return $dirNames;
	}
	public function delete($dirObj, $validateOnly=false)
	{
		//be careful this function will empty any directory and delete it
		//imagine calling with "/"....
		$exist	= $this->isDirectory($dirObj, false);
		if ($exist === true) {
			
			//validate everything first
			if ($dirObj->getWritable() === false) {
				throw new \Exception("Cannot Delete: " . $dirObj->getPathAsString());
			}

			//check sub elements
			$items	= scandir($dirObj->getPathAsString());
			foreach ($items as $item) {
				$item	= trim($item);
				if ($item != "." && $item != "..") {
					$nPath	= $dirObj->getPathAsString() . $item;
					$type	= $this->getType($nPath);
					if ($type == "directory") {
						//recurse
						$nDirObj	= \MTM\FS\Factories::getDirectories()->getDirectory($nPath);
						$this->delete($nDirObj, true);
					} else {
						//this is a file, can we delete it?
						$nFileObj	= \MTM\FS\Factories::getFiles()->getFile($item, $dirObj->getPathAsString());
						if ($nFileObj->getWritable() === false) {
							throw new \Exception("Cannot Delete: " . $nPath);
						}
					}
				}
			}
			
			if ($validateOnly === true) {
				return;
			} else {
				//validation succeeded, go ahead and delete all folders and files
				$items	= scandir($dirObj->getPathAsString());
				foreach ($items as $item) {
					$item	= trim($item);
					if ($item != "." && $item != "..") {
						$rPath	= $dirObj->getPathAsString() . $item;
						$type	= $this->getType($rPath);
						if ($type == "directory") {
							$rDirObj	= \MTM\FS\Factories::getDirectories()->getDirectory($rPath);
							$this->delete($rDirObj, false);
						} else {
							//this is a file, delete it
							$rFileObj	= \MTM\FS\Factories::getFiles()->getFile($item, $dirObj->getPathAsString());
							$rFileObj->delete();
						}
					}
				}
				
				//finally delete the directory
				$valid	= @rmdir($dirObj->getPathAsString());
				if ($valid === false) {
					//something changed after validation
					throw new \Exception("Failed to Delete: " . $dirObj->getPathAsString());
				}
			}
		}
	}
	public function getFreeBytes($dirObj)
	{
		$this->isDirectory($dirObj, true);
		$bytes	= disk_free_space($dirObj->getPathAsString());
		if ($bytes !== false) {
			//returns float, disks can be quite large, so no casting as int
			return $bytes;
		} else {
			throw new \Exception("Failed to get free bytes: " . $dirObj->getPathAsString());
		}
	}
	public function setMode($dirObj, $mode)
	{
		$this->isDirectory($dirObj, true);
		$valid	= chmod($dirObj->getPathAsString(), $mode);
		if ($valid === false) {
			throw new \Exception("Failed to set mode: " . $dirObj->getPathAsString());
		}
	}
	public function getDirSep()
	{
		return DIRECTORY_SEPARATOR;
	}
	public function clearStats($dirObj)
	{
		//clear from php cache
		clearstatcache(true, $dirObj->getPathAsString());
	}
}