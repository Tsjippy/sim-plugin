<?php
namespace SIM\FILEUPLOAD;
use SIM;

class FileUpload{
	public $userId;
	public $metaKey;
	public $metaValue;
	public $library;
	public $callback;
	public $updatemeta;
	public $html;
	
	/**
	 * Constructs the fileupload object
	 *
	 * @param 	int		$userId		The wp WP_User id
	 * @param	string	$metaKey	The key for storage in the user meta or options table. Default empty
	 * @param	bool	$library	Whether to attach the upload to the wp library. Default false
	 * @param	string	$callback	The callback function to call after upload. Default empty
	 * @param	bool	$updatemeta	Whether or not to update the user meta. Default true
	 * @param	string	$metaValue	The key for storage in the user meta or options table. Default empty
	 */
	public function __construct($userId, $metaKey='', $library=false, $callback='', $updatemeta=true, $metaValue='') {
		$this->userId		= $userId;
		$this->metaKey		= $metaKey;
		$this->metaValue	= $metaValue;
		$this->library		= $library;
		$this->callback		= $callback;
		$this->updatemeta	= $updatemeta;

		//Load js
		wp_enqueue_script('sim_fileupload_script');

		// Will only work if vimeo module is enabled
		// Exposes the vimeoUploader variable
		wp_enqueue_script('sim_vimeo_uploader_script');

		wp_enqueue_style( 'sim_image-edit');
	}

	/**
	 * Finds the value in the user meta or options table of a given metaKey
	 */
	public function processMetaKey(){
		if(empty($this->metaKey)){
			return '';
		}

		if(!empty($this->metaValue)){
			return $this->metaValue;
		}

		//get the basemetaKey in case of an indexed one
		if(preg_match('/(.*?)\[/', $this->metaKey, $match)){
			$baseMetaKey	= $match[1];
		}else{
			//just use the whole, it is not indexed
			$baseMetaKey	= $this->metaKey;
		}
		
		//get the db value
		if(is_numeric($this->userId)){
			$documentArray = get_user_meta($this->userId, $baseMetaKey, true);
		}else{
			$documentArray = get_option($baseMetaKey);
		}
		
		//get subvalue if needed
		$documentArray = SIM\getMetaArrayValue($this->userId, $this->metaKey, $documentArray);

		return $documentArray;
	}
	
	/**
	 * Renders the upload button
	 * @param	string	$documentName		The name to use for the files input and storage in db
	 * @param	string	$targetDir			The subfolder of the uploads folder. Default empty
	 * @param	bool	$multiple			Whether to allow multiple files to be uploaded. Default false
	 * @param	string	$options			Extra options to add to the files input element
	 * @param	bool	$editBeforeUpload	Whether or not people can edit a picture before uploading it, default false
	 *
	 * @return	string						The input html
	 */
	public function getUploadHtml($documentName, $targetDir='', $multiple=false, $options='', $editBeforeUpload=false){
		$documentArray = $this->processMetaKey();

		$fileClass	= '';
		if($editBeforeUpload){
			$this->html	= "<div class='image-edit-modal-trigger'></div>";
			$fileClass	= 'should-edit';
		}
		
		$this->html .= '<div class="file-upload-wrap">';
			$this->html .= '<div class="document-preview">';

			if(is_array($documentArray) && !empty($documentArray)){
				foreach($documentArray as $documentKey => $document){
					if(!$this->documentPreview($document, $documentKey, $multiple)){
						// remove from document array if the file is not valid
						unset($documentArray[$documentKey]);
					}
				}
			}elseif(!is_array($documentArray) && $documentArray != ""){
				if(!$this->documentPreview($documentArray, -1, $multiple)){
					$documentArray	= '';
				}
			}
			
			$class 		= '';
			$inputName	= "{$documentName}-files";
			if($multiple){
				$multipleString 	 = 'multiple="multiple"';
				$inputName			.= '[]';

			}else{
				$multipleString = '';
				if(!empty($documentArray)){
					$class = "hidden";
				}
			}

			$this->html .= '</div>';
		
			$this->html .= "<div class='upload-div $class'>";
				$this->html .= "<input class='file-upload $fileClass' type='file' name='$inputName' $multipleString $options>";
				$this->html .= "<div style='width:100%; display: flex;'>";
					if(is_numeric($this->userId)){
						$this->html .= "<input type='hidden' class='no-reset' name='fileupload[user-id]' 			value='{$this->userId}'>";
					}
					if(!empty($targetDir)){
						$targetDir	= str_replace('\\', '/', $targetDir);
						$this->html .= "<input type='hidden' class='no-reset' name='fileupload[targetDir]' 		value='{$targetDir}'>";
					}
					if(!empty($this->metaKey)){
						$this->html .= "<input type='hidden' class='no-reset' name='fileupload[metakey]' 		value='{$this->metaKey}'>";
						$this->html .= "<input type='hidden' class='no-reset' name='fileupload[metakey-index]' 	value='$documentName'>";
					}
					if(!empty($this->library)){
						$this->html .= "<input type='hidden' class='no-reset' name='fileupload[library]' 		value='{$this->library}'>";
					}
					if(!empty($this->callback)){
						$this->html .= "<input type='hidden' class='no-reset' name='fileupload[callback]' 		value='{$this->callback}'>";
					}

					$this->html .= "<input type='hidden' class='no-reset' name='fileupload[updatemeta]' 		value='{$this->updatemeta}'>";
		
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
	 * @param	bool		$multiple		Whether to allow multiple files to be uploaded. Default false
	 */
	public function documentPreview($documentPath, $index, $multiple=false){
		$metaValue		= $documentPath;

		if(is_array($documentPath)){
			if(count($documentPath) == 1){
				$documentPath	= array_values($documentPath)[0];
			}else{
				return 'Please supply a string, not an array';
			}
		}

		if(is_numeric($documentPath) && $this->library){
			$url = wp_get_attachment_url($documentPath);

			if($url === false){
				return false;
			}else{
				$libraryId		= $documentPath;
				$documentPath	= $url;
			}
		}elseif(gettype($documentPath) != 'string' || !is_file(SIM\urlToPath($documentPath))){
			return false;
		}

		//documentpath is already an url
		$url = '';
		if(str_contains($documentPath, SITEURL)){
			$url = $documentPath;
		}elseif(!empty($documentPath)){
			$url = SITEURL.'/'.str_replace(ABSPATH, '', $documentPath);
		}

		$name	= $this->metaKey;
		if($multiple){
			$name	.= '[]';
		}
		
		$this->html .= "<div class='document'>";
			$this->html .= "<input type='hidden' class='no-reset' name='$name' value='$metaValue'>";

		//Check if file is an image
		if(getimagesize(SIM\urlToPath($url)) !== false) {
			//Display the image
			$this->html .= "<a href='$url'><img src='$url' alt='picture' loading='lazy' style='height:150px;'></a>";
		//File is not an image
		} else {
			//Display an link to the file
			$fileName = basename($documentPath);
			
			//remove the username from the filename if it is there
			$userName 	= get_userdata($this->userId)->user_login;
			$fileName 	= str_replace($userName.'-','', $fileName);
			
			//add the hyperlink to the file to the html
			$this->html .= '<a href="'.$url.'">'.$fileName.'</a>';
		}
		//Add an remove button
		if($index == -1){
			$metakeyString = $this->metaKey;
		}else{
			$metakeyString = $this->metaKey.'['.$index.']';
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
		
		$this->html .= "<button type='button' class='remove-document button' data-url='$documentPath' data-user-id='{$this->userId}' data-metakey='$metakeyString' $libraryString>X</button>";
		$this->html .= "</div>";

		return true;
	}
}
