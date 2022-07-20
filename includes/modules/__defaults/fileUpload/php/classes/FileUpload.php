<?php
namespace SIM\FILEUPLOAD;
use SIM;

class FileUpload{
	public $userId;
	public $metakey;
	public $library;
	public $callback;
	public $updatemeta;
	
	/**
	 * Constructs the fileupload object
	 * 
	 * @param 	int		$userId		The wp WP_User id
	 * @param	string	$metakey	The key for storage in the user meta or options table. Default empty
	 * @param	bool	$library	Whether to attach the upload to the wp library. Default false
	 * @param	string	$callback	The callback function to call after upload. Default empty
	 * @param	bool	$updatemeta	Whether or not to update the user meta. Default true
	 */
	function __construct($userId, $metakey='', $library=false, $callback='', $updatemeta=true) {
		$this->userId		= $userId;
		$this->metakey		= $metakey;
		$this->library		= $library;
		$this->callback		= $callback;
		$this->updatemeta	= $updatemeta;

		//Load js
		wp_enqueue_script('sim_fileupload_script');

		// Will only work if vimeo module is enabled
		// Exposes the vimeoUploader variable
		wp_enqueue_script('sim_vimeo_uploader_script');
	}

	/**
	 * Finds the value in the user meta or options table of a given metakey
	 */
	function processMetaKey(){
		if(empty($this->metakey)){
			return '';
		}

		//get the basemetakey in case of an indexed one
		if(preg_match('/(.*?)\[/', $this->metakey, $match)){
			$baseMetaKey	= $match[1];
		}else{
			//just use the whole, it is not indexed
			$baseMetaKey	= $this->metakey;
		}
		
		//get the db value
		if(is_numeric($this->userId)){
			$documentArray = get_user_meta($this->userId, $baseMetaKey, true);
		}else{
			$documentArray = get_option($baseMetaKey);
		}
		
		//get subvalue if needed
		$documentArray = SIM\getMetaArrayValue($this->userId, $this->metakey, $documentArray);

		return $documentArray;
	}
	
	/**
	 * Renders the upload button
	 * @param	string	$documentName	The name to use for the files input and storage in db
	 * @param	string	$targetDir		The subfolder of the uploads folder. Default empty
	 * @param	bool	$multiple		Whether to allow multiple files to be uploaded. Default false
	 * @param	string	$options		Extra options to add to the files input element
	 * 
	 * @return	string					The input html
	 */
	function getUploadHtml($documentName, $targetDir='', $multiple=false, $options=''){
		$documentArray = $this->processMetaKey();

		if($multiple){
			$multipleString = 'multiple="multiple"';
			$class = '';
		}else{
			$multipleString = '';
			if(!empty($documentArray)){
				$class = "hidden";
			}
		}
		
		$this->html = '<div class="file_upload_wrap">';
			$this->html .= '<div class="documentpreview">';
			if(is_array($documentArray) && count($documentArray)>0){
				foreach($documentArray as $documentKey => $document){
					$this->documentPreview($document, $documentKey);
				}
			}elseif(!is_array($documentArray) && $documentArray != ""){
				$this->documentPreview($documentArray, -1);
			}
			$this->html .= '</div>';
		
			$this->html .= "<div class='upload_div $class'>";
				$this->html .= "<input class='file_upload' type='file' name='{$documentName}_files[]' $multipleString $options>";
				$this->html .= "<div style='width:100%; display: flex;'>";
					if(is_numeric($this->userId)){
						$this->html .= "<input type='hidden' name='fileupload[userid]' 			value='{$this->userId}'>";
					}
					if(!empty($targetDir)){
						$targetDir	= str_replace('\\', '/', $targetDir);
						$this->html .= "<input type='hidden' name='fileupload[targetDir]' 		value='{$targetDir}'>";
					}
					if(!empty($this->metakey)){
						$this->html .= "<input type='hidden' name='fileupload[metakey]' 		value='{$this->metakey}'>";
						$this->html .= "<input type='hidden' name='fileupload[metakey_index]' 	value='$documentName'>";
					}
					if(!empty($this->library)){
						$this->html .= "<input type='hidden' name='fileupload[library]' 		value='{$this->library}'>";
					}
					if(!empty($this->callback)){
						$this->html .= "<input type='hidden' name='fileupload[callback]' 		value='{$this->callback}'>";
					}

					$this->html .= "<input type='hidden' name='fileupload[updatemeta]' 		value='{$this->updatemeta}'>";
					
					$this->html .= "<div class='loadergif_wrapper hidden'><span class='uploadmessage'></span><img class='loadergif' src='".LOADERIMAGEURL."'></div>";
				$this->html .= "</div>";
			$this->html .= "</div>";
		$this->html .= "</div>";
		
		return $this->html;
	}
	
	/**
	 * Renders the already uploaded images or show the link to a file
	 * 
	 * @param	string|int	$documentPath	The url, filepath or WP attachment id of a file
	 * @param	int			$index			The metakey sub key					
	 */
	function documentPreview($documentPath, $index){
		$metaValue	= $documentPath;
		if(is_numeric($documentPath) && $this->library){
			$url = wp_get_attachment_url($documentPath);

			if($url === false){
				$documentPath	= '';
				$libraryId		= '';
			}else{
				$libraryId		= $documentPath;
				$documentPath	= $url;
			}
		}

		//documentpath is already an url
		if(strpos($documentPath, SITEURL) !== false){
			$url = $documentPath;
		}else{
			$url = SITEURL.'/'.str_replace(ABSPATH, '', $documentPath);
		}
		
		$this->html .= "<div class='document'>";
			$this->html .= "<input type='hidden' name='{$this->metakey}[]' value='$metaValue'>";

		//Check if file is an image
		if(getimagesize(SIM\urlToPath($url)) !== false) {
			//Display the image
			$this->html .= "<a href='$url'><img src='$url' alt='picture' style='width:150px;height:150px;'></a>";
		//File is not an image
		} else {
			//Display an link to the file
			$fileName = basename($documentPath);
			
			//remove the username from the filename if it is there
			$userName 	= get_userdata($this->userId)->user_login;
			$fileName = str_replace($userName.'-','', $fileName);
			
			//add the hyperlink to the file to the html
			$this->html .= '<a href="'.$url.'">'.$fileName.'</a>';
		}
		//Add an remove button
		if($index == -1){
			$metakeyString = $this->metakey;
		}else{
			$metakeyString = $this->metakey.'['.$index.']';
		}
		
		if(!empty($libraryId)){
			$libraryString = " data-libraryid='$libraryId'";
		}else{
			$libraryString = '';
		}
		
		if($this->callback != ''){
			$libraryString .= " data-callback='{$this->callback}'";
		}

		$libraryString .= " data-updatemeta='{$this->updatemeta}'";
		
		$this->html .= "<button type='button' class='remove_document button' data-url='$documentPath' data-userid='{$this->userId}' data-metakey='$metakeyString' $libraryString>X</button>";
		$this->html .= "<img class='remove_document_loader hidden' src='".LOADERIMAGEURL."' style='height:40px;' >";
		$this->html .= "</div>";
	}
}