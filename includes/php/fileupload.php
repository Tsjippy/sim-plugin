<?php
namespace SIM;

//Class to display uploaded files and the upload form
class Fileupload{
	public $userId;
	public $documentName;
	public $targetDir;
	public $multiple;
	public $metakey;
	public $library;
	public $callback;
	public $html;
	private $library_id;
	public $updatemeta;
	
	function __construct($userId, $documentName, $targetDir, $multiple=true, $metakey='', $library=false, $callback='', $updatemeta=true) {
		$this->userId		= $userId;
		$this->documentName = $documentName;
		$this->targetDir	= str_replace('\\','/',$targetDir);
		$this->multiple		= $multiple;
		$this->metakey		= $metakey;
		$this->library		= $library;
		$this->callback		= $callback;
		$this->library_id	= 0;
		$this->updatemeta	= $updatemeta;

		//Load js
		wp_enqueue_script('sim_fileupload_script');

		// Will only work if vimeo module is enabled
		// Exposes the vimeoUploader variable
		wp_enqueue_script('sim_vimeo_uploader_script');
	}
	
	function getUploadHtml($options=''){
		$documentArray = '';

		if(!empty($this->metakey)){
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
			$documentArray = getMetaArrayValue($this->userId, $this->metakey, $documentArray);
		}
		
		if($this->multiple){
			$multiple = 'multiple="multiple"';
			$class = '';
		}else{
			$multiple = '';
			if(!empty($documentArray)) $class = "hidden";
		}
		
		$this->html = '<div class="file_upload_wrap">';
			$this->html .= '<div class="documentpreview">';
			if(is_array($documentArray) and count($documentArray)>0){
				foreach($documentArray as $documentKey => $document){
					$this->html .= $this->documentPreview($document, $documentKey);
				}
			}elseif(!is_array($documentArray) and $documentArray != ""){
				$this->html .= $this->documentPreview($documentArray, -1);
			}
			$this->html .= '</div>';
		
			$this->html .= "<div class='upload_div $class'>";
				$this->html .= "<input class='file_upload' type='file' name='{$this->documentName}_files[]' $multiple $options>";
				$this->html .= "<div style='width:100%; display: flex;'>";
					if(is_numeric($this->userId)){
						$this->html .= "<input type='hidden' name='fileupload[userid]' 			value='{$this->userId}'>";
					}
					if(!empty($this->targetDir)){
						$this->html .= "<input type='hidden' name='fileupload[targetDir]' 		value='{$this->targetDir}'>";
					}
					if(!empty($this->metakey)){
						$this->html .= "<input type='hidden' name='fileupload[metakey]' 		value='{$this->metakey}'>";
						$this->html .= "<input type='hidden' name='fileupload[metakey_index]' 	value='{$this->documentName}'>";
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
	
	//Function to render the already uploaded images or show the link to a file
	function documentPreview($documentPath, $index){
		$metaValue	= $documentPath;
		if(is_numeric($documentPath) and $this->library){
			$url = wp_get_attachment_url($documentPath);

			if($url === false){
				$documentPath		= '';
			}else{
				$this->library_id	= $documentPath;
				$documentPath		= $url;
			}
		}

		//documentpath is already an url
		if(strpos($documentPath, SITEURL) !== false){
			$url = $documentPath;
		}else{
			$url = SITEURL.'/'.str_replace(ABSPATH,'',$documentPath);
		}
		
		$this->html .= "<div class='document'>";
			$this->html .= "<input type='hidden' name='{$this->metakey}[]' value='$metaValue'>";

		//Check if file is an image
		if(getimagesize(urlToPath($url)) !== false) {
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
		
		if($this->library_id != 0){
			$libraryString = " data-libraryid='{$this->library_id}'";
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

//Make upload_files function availbale for AJAX request
add_action ( 'wp_ajax_upload_files', function (){
	if (!empty($_FILES["files"])) {
		$fileParam	= (array)$_POST['fileupload'];
		$files		= $_FILES["files"];
		$maxSize	= wp_max_upload_size();
		if(!empty($fileParam['targetDir'])){
			$targetDir 		= wp_upload_dir()['path'].'/'.sanitize_text_field($fileParam['targetDir']).'/';
		}else{
			$targetDir 		= wp_upload_dir()['path'].'/';
		}
		
		//create folder if it does not exist
		if (!is_dir($targetDir)) {
			mkdir($targetDir, 0777, true);
		}
		
		if(!empty($fileParam['userid'])){
			$userId 	= sanitize_text_field($fileParam['userid']);
			$username 	= get_userdata($userId)->user_login;
		}
		
		if(isset($fileParam['metakey']))		$metaKey 		= sanitize_text_field($fileParam['metakey']);
		if(isset($fileParam['metakey_index']))	$metaKeyIndex 	= sanitize_text_field($fileParam['metakey_index']);
		
		$filesArr = [];
		foreach ($files['name'] as $key => $fileName) {
			//check file size
			if($files['size'][$key] > $maxSize){
				wp_die('File to big, max file size is '.$maxSize/1024/1024 .'MB');
			}
			
			if ($files['name'][$key]) {
				$fileName 	= sanitize_file_name($fileName);
				
				//Create the filename
				$i = 0;
				if(strtolower(substr($fileName, 0, strlen($username))) == strtolower($username)){
					$targetFile = $targetDir.$fileName;
				}else{
					$targetFile = $targetDir.$username.'-'.$fileName;
				}
				
				while (file_exists($targetFile)) {
 					/*// Set http header error
					header('HTTP/1.0 422 File exists');
					// Return error message
					die(json_encode(array('error' => "The file '$file_name' already exists."))); */
					
					$i++;

					if(strtolower(substr($fileName, 0, strlen($username))) == strtolower($username)){
						$targetFile = $targetDir.$i.'-'.$fileName;
					}else{
						$targetFile = $targetDir.$username.'-'.$i.'-'.$fileName;
					}
				}

				//Move the file
				$moved = move_uploaded_file($files['tmp_name'][$key], $targetFile);
				if ($moved) {
					
					$size = array_push($filesArr, ['url' => str_replace(ABSPATH, '', $targetFile)]);

					//Only store url in db if a metakey isset
					if(isset($metaKey)){
						//get the basemetakey in case of an indexed one
						if(preg_match_all('/(.*?)\[(.*?)\]/i', $metaKey, $matches)){
							$baseMetaKey	= $matches[1][0];
							$keys			= $matches[2];
						}else{
							//just use the whole, it is not indexed
							$baseMetaKey	= $metaKey;
						}

						$newValue	= $targetFile;

						//Add to library if needed
						if(isset($fileParam['library']) and $fileParam['library'] == '1'){
							$attachId	= addToLibrary($targetFile);

							$newValue	= $attachId;
							
							//store the id in the array
							$files_arr[$size-1]['id'] = $attachId;
						}
						
						if(!is_numeric($userId)){
							//generic documents
							$metaValue = get_option($baseMetaKey);
						}else{
							$metaValue = get_user_meta( $userId, $baseMetaKey,true);
						}
						
						if(isset($keys)) addToNestedArray($keys, $metaValue, $newValue);
						
						if($metaKeyIndex)	$metaValue[$metaKeyIndex] = $newValue;
						
						if(!is_numeric($userId)){
							//generic documents
							update_option($baseMetaKey, $metaValue);
						}elseif($fileParam['updatemeta']){
							update_user_meta( $userId, $baseMetaKey, $metaValue);
						}
					}
				}else {
					header('HTTP/1.1 500 Internal Server Booboo');
					header('Content-Type: application/json; charset=UTF-8');
					die(json_encode(array('error' => "File is not uploaded")));
				}
			}
		}
		
		if(isset($fileParam['callback'])) call_user_func($fileParam['callback'], $userId);
		
		echo json_encode($filesArr);
		wp_die();
	}else{
		// Set http header error
		header('HTTP/1.0 422 Unprocessable Entity');
		// Return error message
		die(json_encode(array('error' => 'No files found')));
	}
});

add_action( 'rest_api_init', function () {	
	//Route for first names
	register_rest_route( 
		'sim/v1/', 
		'/remove_document', 
		array(
			'methods'				=> 'POST',
			'callback'				=> __NAMESPACE__.'\removeDocument',
			'permission_callback' 	=> '__return_true'
		)
	);
});

function removeDocument(){
	if(!empty($_POST['url'])){
		$path = ABSPATH.$_POST['url'];

		if(isset($_POST['userid']))		$userId = sanitize_text_field($_POST["userid"]);
		if(isset($_POST['metakey']))	$metaKey = sanitize_text_field($_POST['metakey']);
		
		if(isset($metaKey)){
			$metaKeys 		= str_replace(']','',explode('[', $metaKey));
			$baseMetaKey 	= $metaKeys[0];
			unset($metaKeys[0]);
		}
		
		//Just an extra check
		if (strpos($path, 'wp-content/uploads') !== false){
			//remove the file
			if(isset($_POST['libraryid']) and is_numeric($_POST['libraryid'])){
				wp_delete_attachment($_POST['libraryid']);
			}else{
				
				unlink($path);
			}
			
			//Remove the path from db 
			if(is_numeric($userId)){
				//Get document array from db
				$documentsArray = get_user_meta( $userId, $baseMetaKey,true);
			//Generic document
			}else{
				//get documents array from db
				$documentsArray = get_option($baseMetaKey);
			}
			
			//remove from array
			if(is_array($metaKeys) and count($metaKeys)>0){
				
				removeFromNestedArray($documentsArray, $metaKeys);
			}else{
				$documentsArray = '';
			}
				
			//Personnal document
			if(is_numeric($userId)){
				//Store the array in db
				update_user_meta( $userId, $baseMetaKey, $documentsArray);
			//Generic document
			}else{
				//Save it in db
				update_option($baseMetaKey, $documentsArray);
			}
			
			$message = "File successfully removed";
		}else{
			$message = null;
		}
	}else{
		$message = null;
	}
	
	//send message back to js
	return $message;
}

