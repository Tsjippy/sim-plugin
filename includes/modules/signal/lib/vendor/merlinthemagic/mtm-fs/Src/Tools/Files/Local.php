<?php
//© 2019 Martin Madsen
namespace MTM\FS\Tools\Files;

class Local
{
	public function isFile($fileObj, $throw=false)
	{
		$type	= $this->getType($fileObj);
		if ($type == "file") {
			return true;
		} else {
			if ($throw === false) {
				return false;
			} else {
				throw new \Exception("Not a File: " . $fileObj->getPathAsString());
			}
		}
	}
	public function isWritable($input)
	{
		$this->clearStats($input);
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
		$this->clearStats($input);
		if (is_object($input) === true) {
			$input = $input->getPathAsString();
		}
		
		//can we eliminate the exist call? i think get type returns false when file is not there
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
	public function getOwner($fileObj)
	{
		$this->isFile($fileObj, true);
		
		if ($this->getDirSep() == "/") {
			//do not know how to do this without a shell
			$strCmd		= "stat -c '%U' " . "'".$fileObj->getPathAsString()."'";
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
	public function create($fileObj)
	{
		$exist	= $this->isFile($fileObj, false);
		if ($exist === false) {
			//make sure the directory path is created
			$fileObj->getDirectory()->create();
			
			//make sure we can write to the parent dir.
			$canWrite	= $fileObj->getDirectory()->getWritable();
			if ($canWrite === false) {
				throw new \Exception("Cannot Create: " . $fileObj->getPathAsString() . " cannot write to: " . $fileObj->getDirectory()->getPathAsString());
			} else {
				$fh = fopen($fileObj->getPathAsString(), "w");
				if ($fh === false) {
					throw new \Exception("Failed to Create: " . $fileObj->getPathAsString());
				} else {
					fclose($fh);
				}
			}
		}
	}
	public function delete($fileObj)
	{
		$type	= $this->getType($fileObj);
		if ($type !== null) {
			if ($type != "directory") {
				$deleted	= @unlink($fileObj->getPathAsString());
				if ($deleted === false) {
					throw new \Exception("Failed to Delete: " . $fileObj->getPathAsString());
				}
			} else {
				throw new \Exception("Cannot delete directory: " . $fileObj->getPathAsString());
			}
		}
	}
	public function getByteCount($fileObj)
	{
		//not allowing links as we do not know if the link is for a dir or file
		$this->isFile($fileObj, true);
		$this->clearStats($fileObj); //clear cache before getting size, php tends to cache file attrbutes
		$byteSize	= @filesize($fileObj->getPathAsString());
		if ($byteSize === false) {
			//maybe file does not exist
			throw new \Exception("Failed Get Size for: " . $fileObj->getPathAsString());
		} else {
			return $byteSize;
		}
	}
	public function getLineCount($fileObj)
	{
		$this->isFile($fileObj, true);
		//start at -1 or empty files return 1 line
		$lCount = -1;
		$fp		= fopen($fileObj->getPathAsString(), "r");
		while(feof($fp) === false){
			fgets($fp);
			$lCount++;
		}
		fclose($fp);
		return $lCount;
	}
	public function getLines($fileObj, $count, $sLine)
	{
		$rLines	= array();
		$lCount	= $this->getLineCount($fileObj);
		if ($lCount >= $sLine) {
			$cLine	= 0;
			$cCount	= 0;
			$fp		= fopen($fileObj->getPathAsString(), "rb");
			while(feof($fp) === false){
				$line	= fgets($fp);
				$cLine++;
				if ($cLine >= $sLine) {
					
					if ($cCount < $count) {
						$cCount++;
						$rLines[]	= $line;
						
						if ($cLine == $lCount) {
							//this is the last line, we are done. 
							//if we dont break we get an extra empty line
							break;
						}

					} else {
						//we do not need any more lines
						break;
					}
				}
			}
			fclose($fp);
		}

		return $rLines;
	}

	public function getBytes($fileObj, $count, $sByte)
	{
		if ($sByte < 1) {
			throw new \Exception("Start byte must be 1 or greater");
		}

		$rBytes	= null;
		$size	= $this->getByteCount($fileObj); //get byte count clears stats
		if ($size >= $sByte) {

			$fp		= fopen($fileObj->getPathAsString(), "rb");
			$bByte	= ($sByte - 1);
			if ($bByte == 0) {
				//read from the beginning
				$rBytes	= fread($fp, $count);
			} else {
				//read from offset
				fseek($fp, $bByte);
				$rBytes 	= fread($fp, $count);
			}
			fclose($fp);
		}
		
		return $rBytes;
	}
	public function setContent($fileObj, $data=null)
	{
		$exist	= $this->isFile($fileObj, false);
		if ($exist === false) {
			//create file
			$this->create($fileObj);
			
		} else {
			
			$canWrite	= $this->isWritable($fileObj);
			if ($canWrite === false) {
				throw new \Exception("Cannot set content, file is not writable");
			}
			
			//empty the file
			$fp = @fopen($fileObj->getPathAsString(), "r+");
			if ($fp === false) {
				throw new \Exception("Failed to set content");
			} else {
				ftruncate($fp, 0);
				fclose($fp);
			}
		}
		
		$this->clearStats($fileObj); //clear or getContent() will return old content
	
		if ($data !== null) {
			$this->addContent($fileObj, $data, "append");
		}
	}
	public function addContent($fileObj, $data, $type)
	{
		$fileType	= $this->getType($fileObj);
		if ($fileType == "file" || $fileType == "pipe") {
			
			//add content implies the file exists already
			$dLength	= strlen($data);
			if ($dLength > 0) {
				$type	= strtolower($type);

				if ($type == "append") {
					//the "n" means in mode means fopen will not block if a pipe is removed
					$fp			= @fopen($fileObj->getPathAsString(), "an");
				} elseif ($type == "prepend") {
					$fp			= @fopen($fileObj->getPathAsString(), "xn");
				} else {
					throw new \Exception("Invalid Type: " . $type);
				}

				if (is_resource($fp) === true) {
					
					$bWrites	= fwrite($fp, $data);
					fclose($fp);
					
					if ($bWrites === false) {
						//failed to write, maybe file does not exist
						throw new \Exception("Failed Write: " . $fileObj->getPathAsString());
					} elseif ($bWrites != $dLength) {
						throw new \Exception("Failed Complete Write: " . $fileObj->getPathAsString());
					}
				} else {
					throw new \Exception("Cannot add to a file, error opening for writing: " . $fileObj->getPathAsString(), 92987);
				}
			}
			
			$this->clearStats($fileObj); //clear or getContent() will return old content
			
		} elseif ($fileType === null) {
			throw new \Exception("Cannot add to a file that does not exist");
		} else {
			throw new \Exception("Invalid File Type: " . $fileType);
		}
	}
	public function copy($srcFileObj, $dstFileObj, $replace)
	{
		$this->isFile($srcFileObj, true);
		$dstExist	= $dstFileObj->getExists();
		if ($dstExist === true) {
			if ($replace === false) {
				throw new \Exception("Cannot copy src: " . $srcFileObj->getPathAsString(). " to dst: " . $dstFileObj->getPathAsString(). ", dst exists");
			} else {
				//make sure the dst path is writable
				$canWrite	= $dstFileObj->getWritable();
				if ($canWrite === false) {
					throw new \Exception("Cannot copy src: " . $srcFileObj->getPathAsString(). " to dst: " . $dstFileObj->getPathAsString(). ", dst not writable");
				}
			}
		} else {
			//make sure the dst directory path is created
			$dstFileObj->getDirectory()->create();
		}

		$valid	= copy($srcFileObj->getPathAsString(), $dstFileObj->getPathAsString());
		if ($valid === false) {
			throw new \Exception("Failed copy: " . $srcFileObj->getPathAsString(). " to: " . $dstFileObj->getPathAsString());
		}

		$this->clearStats($dstFileObj); //clear stats on dst file or getContent() will return old content
	}
	public function move($srcFileObj, $dstFileObj, $replace)
	{
		$this->copy($srcFileObj, $dstFileObj, $replace);
		$this->delete($srcFileObj);
		$this->clearStats($srcFileObj); //clear stats on src file or getContent() will return old content
		$this->clearStats($dstFileObj); //clear stats on dst file or getContent() will return old content
	}
	 
	public function setMode($fileObj, $mode)
	{
		//decimal input
		$this->isFile($fileObj, true);
		$mLen	= strlen($mode);
		if (ctype_digit((string) $mode) === false || $mLen > 4) {
			//need more checks to verify the mode is valid
			throw new \Exception("Mode not valid");
		} elseif ($mLen == 3) {
			$mode	= "0" . $mode;
		} elseif ($mLen == 4) {
			
			if (substr($mode, 0, 1) == 1) {
				//http://php.net/manual/en/function.chmod.php#31383
				//if we want to use a sticky bit the mode becomes 5 long
				$mode	= "0" . $mode;
			}
		}

		$this->clearStats($fileObj);
		//function takes octal input
		$valid	= @chmod($fileObj->getPathAsString(), intval($mode, 8));
		if ($valid === false) {
			throw new \Exception("Failed to set mode: " . $fileObj->getPathAsString());
		}
	}
	public function getMode($fileObj)
	{
		$this->isFile($fileObj, true);
		$this->clearStats($fileObj);
		return substr(sprintf("%o", @fileperms($fileObj->getPathAsString())), -4);
	}
	public function getDirSep()
	{
		return DIRECTORY_SEPARATOR;
	}
	public function clearStats($input)
	{
		//src: https://www.php.net/manual/en/function.clearstatcache.php
		//limit to use before calls to:
		//stat(), lstat(), file_exists(), is_writable(), is_readable(), is_executable(), is_file(), is_dir(), is_link(), filectime(), fileatime(), filemtime(), fileinode(), filegroup(), fileowner(), filesize(), filetype(), and fileperms(). 
		//clear from php cache
		if (is_object($input) === true) {
			$input = $input->getPathAsString();
		}
		clearstatcache(true, $input);
	}
}