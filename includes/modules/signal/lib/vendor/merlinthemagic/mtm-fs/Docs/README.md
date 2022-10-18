### What is this?

A wrapper for working with files and directories

#### Files
```
$fileObj	= \MTM\FS\Factories::getFiles()->getFile("file.txt", "/tmp/");
```

##### Get file name
```
echo $fileObj->getName(); // file.txt
```

##### Get Directory
```
$dirObj	= $fileObj->getDirectory();
```

##### Get path as string
```
echo $fileObj->getPathAsString(); // /tmp/file.txt
```

##### Get file extension
```
echo $fileObj->getExtension(); // txt
```

##### Get file exists
```
echo $fileObj->getExists(); // true / false
```

##### Get file is writable
```
echo $fileObj->getWritable(); // true / false
```

##### Get file type
```
echo $fileObj->getType(); // pipe / file / link / socket
```

##### Get file owner
```
echo $fileObj->getOwner(); // root / centos / apache etc
```

##### Create file
```
$fileObj->create();
```

##### Delete file
```
$fileObj->delete();
```

##### Get file line count
```
echo $fileObj->getLineCount(); // int
```

##### Get specific lines from file
```
$numberOfLines	= 10;
$firstLine			= 50;
echo $fileObj->getLines($numberOfLines, $firstLine); // array with 10 lines
```

##### Get file byte size
```
echo $fileObj->getByteCount(); // int
```

##### Get specific bytes from file
```
$numberOfBytes	= 451;
$firstByte			= 12;
echo $fileObj->getBytes($numberOfBytes, $firstByte); // string | binary 451 bytes of data
```

##### Set file content
```
$data	= "Content for my file";
$fileObj->setContent($data);
```

##### Get file content
```
echo $fileObj->getContent(); // all data from file
```

##### Add content to file
```
$data	= "more data for my file"
$type	= "append";
$fileObj->addContent($data, $type);
```

##### set file permissions
```
$mode	= "644";
$fileObj->setMode($mode);
```

##### Get file permissions
```
echo $fileObj->getMode(); // 777 | 644 etc
```

##### Copy file
```
$replaceIfExists	= true;
$dstFileObj		= \MTM\FS\Factories::getFiles()->getFile("anotherFile.txt", "/tmp/");
$fileObj->copy($dstFileObj, $replaceIfExists);
```

##### Move file
```
$replaceIfExists	= true;
$dstFileObj		= \MTM\FS\Factories::getFiles()->getFile("anotherFile.txt", "/tmp/");
$fileObj->move($dstFileObj, $replaceIfExists);
```

##### Get file object clone
```
$clonedObj		= $fileObj->getClone();
```


#### Directories
```
$dirObj	= \MTM\FS\Factories::getDirectories()->getDirectory("/tmp/myDir");
```


##### Get Directory name
```
echo $dirObj->getName(); // myDir
```

##### Get path as string
```
echo $dirObj->getPathAsString(); // /tmp/myDir
```

##### Get Parent
```
$dirObj		= $dirObj->getParent(); // one level down
```

##### Add child file / directory
```
$fileObj	= \MTM\FS\Factories::getFiles()->getFile("file.txt");
$dirObj->addChild($fileObj); // /sets file directory to the $dirObj

$dirObj2	= \MTM\FS\Factories::getDirectories()->getDirectory("mySecondDir");
$dirObj->addChild($dirObj2); // /sets parent directory to the $dirObj
```

##### Get Children
```
$objs	= $dirObj->getChildren(); // files and directories
```

##### Get Child by name
```
$return	= $dirObj->getChildByName(); // file | dir obj | null if name does not exist
```

##### Append Directory
```
$nextDir	= "myThirdDir";
$newDir		= $dirObj->appendDirectory($nextDir); // has original dir as parent
```

##### Get exists
```
echo $dirObj->getExists(); // true | false
```

##### Get is writable
```
echo $dirObj->getWritable(); // true | false
```

##### Get Type
```
echo $dirObj->getType(); // directory | null if not exist
```

##### Get Owner
```
echo $dirObj->getOwner(); // apache | root etc
```

##### Create Directory, if there are intermediate directories missing they will be created too
```
$dirObj->create();
```

##### Delete, if there are children, dirs and files they will be deleted too
```
$dirObj->delete();
```

##### Populate children from file system
```
$recursive	= false; //if true, every discovered directory will also be populated. careful with this.
$dirObj->setFromSystem($recursive);
```

##### Get names of child files
```
$fileNames	= $dirObj->getFileNames(); // array of strings, cheaper than populating objects
```

##### Get names of child directories
```
$dirNames	= $dirObj->getDirectoryNames(); // array of strings, cheaper than populating objects
```

##### Get Clone
```
$clonedDir	= $dirObj->getClone();
```

#### Lock Files
```

$filename	= "my.lock";
$maxAge	= 30; //if not updated in 30 seconds we assume the lock is dead
$lockObj	= \MTM\FS\Factories::getFiles()->getLockFile($filename, $maxAge);
```

