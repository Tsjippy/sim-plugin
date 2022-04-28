<?php
namespace SIM;

//Class to display uploaded files and the upload form
class Fileupload{
	public $user_id;
	public $documentname;
	public $targetdir;
	public $multiple;
	public $metakey;
	public $library;
	public $callback;
	public $html;
	private $library_id;
	public $updatemeta;
	
	function __construct($user_id, $documentname, $targetdir, $multiple=true, $metakey='', $library=false, $callback='', $updatemeta=true) {
		$this->user_id		= $user_id;
		$this->documentname = $documentname;
		$this->targetdir	= str_replace('\\','/',$targetdir);
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
	
	function get_upload_html($options=''){
		$document_array = '';

		if(!empty($this->metakey)){
			//get the basemetakey in case of an indexed one
			if(preg_match('/(.*?)\[/', $this->metakey, $match)){
				$base_meta_key	= $match[1];
			}else{
				//just use the whole, it is not indexed
				$base_meta_key	= $this->metakey;
			}
			
			//get the db value
			if(is_numeric($this->user_id)){
				$document_array = get_user_meta($this->user_id, $base_meta_key, true);
			}else{
				$document_array = get_option($base_meta_key);
			}
			
			//get subvalue if needed
			$document_array = get_meta_array_value($this->user_id, $this->metakey, $document_array);
		}
		
		if($this->multiple){
			$multiple = 'multiple="multiple"';
			$class = '';
		}else{
			$multiple = '';
			if(!empty($document_array)) $class = "hidden";
		}
		
		$this->html = '<div class="file_upload_wrap">';
			$this->html .= '<div class="documentpreview">';
			if(is_array($document_array) and count($document_array)>0){
				foreach($document_array as $document_key => $document){
					$this->html .= $this->document_preview($document, $document_key);
				}
			}elseif(!is_array($document_array) and $document_array != ""){
				$this->html .= $this->document_preview($document_array, -1);
			}
			$this->html .= '</div>';
		
			$this->html .= "<div class='upload_div $class'>";
				$this->html .= "<input class='file_upload' type='file' name='{$this->documentname}_files[]' $multiple $options>";
				$this->html .= "<div style='width:100%; display: flex;'>";
					if(is_numeric($this->user_id)){
						$this->html .= "<input type='hidden' name='fileupload[userid]' 			value='{$this->user_id}'>";
					}
					if(!empty($this->targetdir)){
						$this->html .= "<input type='hidden' name='fileupload[targetdir]' 		value='{$this->targetdir}'>";
					}
					if(!empty($this->metakey)){
						$this->html .= "<input type='hidden' name='fileupload[metakey]' 		value='{$this->metakey}'>";
						$this->html .= "<input type='hidden' name='fileupload[metakey_index]' 	value='{$this->documentname}'>";
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
	function document_preview($document_path, $index){
		$meta_value	= $document_path;
		if(is_numeric($document_path) and $this->library){
			$url = wp_get_attachment_url($document_path);

			if($url === false){
				$document_path		= '';
			}else{
				$this->library_id	= $document_path;
				$document_path		= $url;
			}
		}

		//documentpath is already an url
		if(strpos($document_path, SITEURL) !== false){
			$url = $document_path;
		}else{
			$url = SITEURL.'/'.str_replace(ABSPATH,'',$document_path);
		}
		
		$this->html .= "<div class='document'>";
			$this->html .= "<input type='hidden' name='{$this->metakey}[]' value='$meta_value'>";

		//Check if file is an image
		if(getimagesize(url_to_path($url)) !== false) {
			//Display the image
			$this->html .= "<a href='$url'><img src='$url' alt='picture' style='width:150px;height:150px;'></a>";
		//File is not an image
		} else {
			//Display an link to the file
			$filename = basename($document_path);
			
			//remove the username from the filename if it is there
			$username 	= get_userdata($this->user_id)->user_login;
			$filename = str_replace($username.'-','',$filename);
			
			//add the hyperlink to the file to the html
			$this->html .= '<a href="'.$url.'">'.$filename.'</a>';
		}
		//Add an remove button
		if($index == -1){
			$metakey_string = $this->metakey;
		}else{
			$metakey_string = $this->metakey.'['.$index.']';
		}
		
		if($this->library_id != 0){
			$library_string = " data-libraryid='{$this->library_id}'";
		}else{
			$library_string = '';
		}
		
		if($this->callback != ''){
			$library_string .= " data-callback='{$this->callback}'";
		}

		$library_string .= " data-updatemeta='{$this->updatemeta}'";
		
		$this->html .= "<button type='button' class='remove_document button' data-url='$document_path' data-userid='{$this->user_id}' data-metakey='$metakey_string' $library_string>X</button>";
		$this->html .= "<img class='remove_document_loader hidden' src='".LOADERIMAGEURL."' style='height:40px;' >";
		$this->html .= "</div>";
	}
}

//Make upload_files function availbale for AJAX request
add_action ( 'wp_ajax_upload_files', 'SIM\upload_files' );
function upload_files(){
	if (!empty($_FILES["files"])) {
		$file_param	= (array)$_POST['fileupload'];
		$files		= $_FILES["files"];
		$max_size	= wp_max_upload_size();
		if(!empty($file_param['targetdir'])){
			$targetdir 		= wp_upload_dir()['path'].'/'.sanitize_text_field($file_param['targetdir']).'/';
		}else{
			$targetdir 		= wp_upload_dir()['path'].'/';
		}
		
		//create folder if it does not exist
		if (!is_dir($targetdir)) {
			mkdir($targetdir, 0777, true);
		}
		
		if(!empty($file_param['userid'])){
			$user_id 	= sanitize_text_field($file_param['userid']);
			$username 	= get_userdata($user_id)->user_login;
		}
		
		if(isset($file_param['metakey']))		$meta_key 		= sanitize_text_field($file_param['metakey']);
		if(isset($file_param['metakey_index']))	$metakey_index 	= sanitize_text_field($file_param['metakey_index']);
		
		$files_arr = [];
		foreach ($files['name'] as $key => $file_name) {
			//check file size
			if($files['size'][$key] > $max_size){
				wp_die('FIle to big, max file size is '.$max_size/1024/1024 .'MB');
			}
			
			if ($files['name'][$key]) {
				$file_name 	= sanitize_file_name($file_name);
				
				//Create the filename
				$i = 0;
				if(strtolower(substr($file_name, 0, strlen($username))) == strtolower($username)){
					$target_file = $targetdir.$file_name;
				}else{
					$target_file = $targetdir.$username.'-'.$file_name;
				}
				
				while (file_exists($target_file)) {
 					/*// Set http header error
					header('HTTP/1.0 422 File exists');
					// Return error message
					die(json_encode(array('error' => "The file '$file_name' already exists."))); */
					
					$i++;

					if(strtolower(substr($file_name, 0, strlen($username))) == strtolower($username)){
						$target_file = $targetdir.$i.'-'.$file_name;
					}else{
						$target_file = $targetdir.$username.'-'.$i.'-'.$file_name;
					}
				}

				//Move the file
				$moved = move_uploaded_file($files['tmp_name'][$key], $target_file);
				if ($moved) {
					
					$size = array_push($files_arr, ['url' => str_replace(ABSPATH, '', $target_file)]);

					//Only store url in db if a metakey isset
					if(isset($meta_key)){
						//get the basemetakey in case of an indexed one
						if(preg_match_all('/(.*?)\[(.*?)\]/i', $meta_key, $matches)){
							$base_meta_key	= $matches[1][0];
							$keys			= $matches[2];
						}else{
							//just use the whole, it is not indexed
							$base_meta_key	= $meta_key;
						}

						$new_value	= $target_file;

						//Add to library if needed
						if(isset($file_param['library']) and $file_param['library'] == '1'){
							$attach_id	= add_to_library($target_file);

							$new_value	= $attach_id;
							
							//store the id in the array
							$files_arr[$size-1]['id'] = $attach_id;
						}
						
						if(!is_numeric($user_id)){
							//generic documents
							$meta_value = get_option($base_meta_key);
						}else{
							$meta_value = get_user_meta( $user_id, $base_meta_key,true);
						}
						
						if(isset($keys)) add_to_nested_array($keys, $meta_value, $new_value);
						
						if($metakey_index)	$meta_value[$metakey_index] = $new_value;
						
						if(!is_numeric($user_id)){
							//generic documents
							update_option($base_meta_key, $meta_value);
						}elseif($file_param['updatemeta']){
							update_user_meta( $user_id, $base_meta_key, $meta_value);
						}
					}
				}else {
					header('HTTP/1.1 500 Internal Server Booboo');
					header('Content-Type: application/json; charset=UTF-8');
					die(json_encode(array('error' => "File is not uploaded")));
				}
			}
		}
		
		if(isset($file_param['callback'])) call_user_func($file_param['callback'],$user_id);
		
		echo json_encode($files_arr);
		wp_die();
	}else{
		// Set http header error
		header('HTTP/1.0 422 Unprocessable Entity');
		// Return error message
		die(json_encode(array('error' => 'No files found')));
	}
}

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

		if(isset($_POST['userid']))		$user_id = sanitize_text_field($_POST["userid"]);
		if(isset($_POST['metakey']))	$metakey = sanitize_text_field($_POST['metakey']);
		
		if(isset($metakey)){
			$meta_keys = str_replace(']','',explode('[',$metakey));
			$base_meta_key = $meta_keys[0];
			unset($meta_keys[0]);
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
			if(is_numeric($user_id)){
				//Get document array from db
				$documents_array = get_user_meta( $user_id, $base_meta_key,true);
			//Generic document
			}else{
				//get documents array from db
				$documents_array = get_option($base_meta_key);
			}
			
			//remove from array
			if(is_array($meta_keys) and count($meta_keys)>0){
				
				remove_from_nested_array($documents_array, $meta_keys);
			}else{
				$documents_array = '';
			}
				
			//Personnal document
			if(is_numeric($user_id)){
				//Store the array in db
				update_user_meta( $user_id, $base_meta_key, $documents_array);
			//Generic document
			}else{
				//Save it in db
				update_option($base_meta_key,$documents_array);
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

