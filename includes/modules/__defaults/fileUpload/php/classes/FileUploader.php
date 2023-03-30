<?php
namespace SIM\FILEUPLOAD;
use SIM;

class FileUploader{
    public $fileParam;
    public $maxSize;
    public $userId;
    public $username;
    public $metaKey;
    public $targetDir;
    public $files;
    public $filesArr;
    public $metaKeyIndex;
    public $key;
    public $fileName;
    public $targetFile;

    public function __construct($settings, $files){
        $this->fileParam	= (array)$settings['fileupload'];
        $this->maxSize	    = wp_max_upload_size();
        $this->userId       = 0;
        $this->username     = '';
        $this->metaKey      = '';
        $this->metaKeyIndex = '';
        $this->filesArr     = [];
        $this->files        = $files;
        if(!empty($this->fileParam['targetDir'])){
            $this->targetDir 		= wp_upload_dir()['basedir'].'/'.sanitize_text_field($this->fileParam['targetDir']).'/';
        }else{
            $this->targetDir 		= wp_upload_dir()['basedir'].'/';
        }
        
        //create folder if it does not exist
        if (!is_dir($this->targetDir)) {
            mkdir($this->targetDir, 0777, true);
        }
        
        if(!empty($this->fileParam['userid'])){
            $this->userId 	    = sanitize_text_field($this->fileParam['userid']);
            $this->username 	= get_userdata($this->userId)->user_login;
        }
        
        if(isset($this->fileParam['metakey'])){
            $this->metaKey 		= sanitize_text_field($this->fileParam['metakey']);
        }
    
        if(isset($this->fileParam['metakey_index'])){
            $this->metaKeyIndex 	= sanitize_text_field($this->fileParam['metakey_index']);
        }

        $this->processFiles();

        if(isset($this->fileParam['callback'])){
            call_user_func($this->fileParam['callback'], $this->userId);
        }
    }

    public function processFiles(){
        foreach ($this->files['name'] as $this->key => $this->fileName) {
            //check file size
            if($this->files['size'][$this->key] > $this->maxSize){
                wp_die('File to big, max file size is '.$this->maxSize/1024/1024 .'MB');
            }

            $this->findFileName();

            $this->moveFile();

            if(!empty($this->metaKey)){
                $this->addToDb();
            }
        }
    }

    /**
     * Finds the first available filename
     */
    public function findFileName(){
        $this->fileName 	= sanitize_file_name($this->fileName);
            
        //Create the filename
        $i = 0;
        if(strtolower(substr($this->fileName, 0, strlen($this->username))) == strtolower($this->username)){
            $this->targetFile = $this->targetDir.$this->fileName;
        }else{
            $this->targetFile = $this->targetDir.$this->username.'-'.$this->fileName;
        }

        while (file_exists($this->targetFile)) {
            $i++;

            if(strtolower(substr($this->fileName, 0, strlen($this->username))) == strtolower($this->username)){
                $this->targetFile = $this->targetDir.$i.'-'.$this->fileName;
            }else{
                $this->targetFile = $this->targetDir.$this->username.'-'.$i.'-'.$this->fileName;
            }
        }
    }

    public function moveFile(){
        //Move the file
        $moved = move_uploaded_file($this->files['tmp_name'][$this->key], $this->targetFile);

        if(!$moved){
            header('HTTP/1.1 500 Internal Server Booboo');
            header('Content-Type: application/json; charset=UTF-8');
            die(json_encode(array('error' => "File is not uploaded")));
        }

        // Add the url to the files array
        array_push($this->filesArr, ['url' => str_replace(ABSPATH, '', $this->targetFile)]);
    }

    public function addToDb(){
        //get the basemetakey in case of an indexed one
        if(preg_match_all('/(.*?)\[(.*?)\]/i', $this->metaKey, $matches)){
            $baseMetaKey	= $matches[1][0];
            $keys			= $matches[2];
        }else{
            //just use the whole, it is not indexed
            $baseMetaKey	= $this->metaKey;
        }

        $newValue	= $this->targetFile;

        //Add to library if needed
        if(isset($this->fileParam['library']) && $this->fileParam['library'] == '1'){
            $attachId	= SIM\addToLibrary($this->targetFile);

            $newValue	= $attachId;
            
            //store the id in the array
            $this->filesArr[count($this->filesArr)-1]['id'] = $attachId;
        }
        
        if(!is_numeric($this->userId)){
            //generic documents
            $metaValue = get_option($baseMetaKey);
        }else{
            $metaValue = get_user_meta( $this->userId, $baseMetaKey,true);
        }
        
        if(isset($keys)){
            SIM\addToNestedArray($keys, $metaValue, $newValue);
        }
        
        if(!empty($this->metaKeyIndex)){
            if(!is_array($metaValue)){
                $metaValue  = [];
            }
            $metaValue[$this->metaKeyIndex] = $newValue;
        }
        
        if(!is_numeric($this->userId)){
            //generic documents
            update_option($baseMetaKey, $metaValue);
        }elseif($this->fileParam['updatemeta']){
            update_user_meta( $this->userId, $baseMetaKey, $metaValue);
        }
    }
}
