<?php
namespace SIM\FILEUPLOAD;
use SIM;

class FileUpload{
	public $userId;
	public $metakey;
	public $library;
	public $callback;
	public $updatemeta;
	public $html;
	
	/**
	 * Constructs the fileupload object
	 *
	 * @param 	int		$userId		The wp WP_User id
	 * @param	string	$metakey	The key for storage in the user meta or options table. Default empty
	 * @param	bool	$library	Whether to attach the upload to the wp library. Default false
	 * @param	string	$callback	The callback function to call after upload. Default empty
	 * @param	bool	$updatemeta	Whether or not to update the user meta. Default true
	 */
	public function __construct($userId, $metakey='', $library=false, $callback='', $updatemeta=true) {
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

		wp_enqueue_style( 'sim_image-edit');
	}

	/**
	 * Finds the value in the user meta or options table of a given metakey
	 */
	public function processMetaKey(){
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

	public function imageEditModal(){
		global $editImageModalAdded;

		// only add the modal once
		if($editImageModalAdded){
			return '';
		}else{
			$editImageModalAdded	= true;
		}

		$basePicturesUrl	= plugins_url('../pictures/', __DIR__);

		ob_start();

		?>
		<div id="edit-image-modal" class="modal edit-image hidden">
			<!-- Modal content -->
			<div class="modal-content">
				<span id="modal-close" class="close">&times;</span>

				<div class="image-edit-container">
					<h4>Edit your image</h4>
					<div class="image-edit-wrapper">
						<div class="editor-panel">
							<div class="filter">
								<label class="title">Filters</label>
								<div class="options">
									<button id="brightness" type="button" class="active">Brightness</button>
									<button id="saturation" type="button">Saturation</button>
									<button id="inversion" type="button">Inversion</button>
									<button id="grayscale" type="button">Grayscale</button>
								</div>
								<div class="slider">
									<div class="filter-info">
										<p class="name">Brighteness</p>
										<p class="value">100%</p>
									</div>
									<input type="range" value="100" min="0" max="200">
								</div>
							</div>
							<div class="rotate">
								<label class="title">Rotate</label>
								<div class="options">
									<button id="left" type="button"><img src='<?php echo $basePicturesUrl;?>rotate-left-solid.svg' alt='rotate left'></button>
									<button id="right" type="button"><img src='<?php echo $basePicturesUrl;?>rotate-right-solid.svg' alt='rotate right'></i></button>
									<button id="horizontal" type="button"><img src='<?php echo $basePicturesUrl;?>reflect-vertical.svg' alt='reflect vertical'></button>
                        			<button id="vertical" type="button"><img src='<?php echo $basePicturesUrl;?>reflect-horizontal.svg' alt='reflect horizontal'></button>
								</div>
							</div>
						</div>
						<div class="preview-img">
							<img src="" alt="preview-img" class='hidden'>
							<div class="break"></div>
							<div class="zoom" style='margin-top:10px;'>
								Zoom<br>
								<input class='image-zoom' type="range" value="50" min="0" max="100">
								<output>50</output>%
							</div>
						</div>
						
					</div>
					<div class="controls">
						<button type="button" class="reset-filter">Reset Filters</button>
						<div class="row">
							<input type="file" class="file-input" accept="image/*" hidden>
							<button type="button" class="choose-img">Change Image</button>
							<button type="button" class="save-img">Save Image</button>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php

		return ob_get_clean();
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
			$this->html	= $this->imageEditModal();
			$fileClass	= 'should-edit';
		}
		
		$this->html .= '<div class="file-upload-wrap">';
			$this->html .= '<div class="document-preview">';

			if(is_array($documentArray) && !empty($documentArray)){
				foreach($documentArray as $documentKey => $document){
					if(!$this->documentPreview($document, $documentKey)){
						// remove from document array if the file is not valid
						unset($documentArray[$documentKey]);
					}
				}
			}elseif(!is_array($documentArray) && $documentArray != ""){
				if(!$this->documentPreview($documentArray, -1)){
					$documentArray	= '';
				}
			}
			
			$class = '';
			if($multiple){
				$multipleString = 'multiple="multiple"';
			}else{
				$multipleString = '';
				if(!empty($documentArray)){
					$class = "hidden";
				}
			}

			$this->html .= '</div>';
		
			$this->html .= "<div class='upload-div $class'>";
				$this->html .= "<input class='file-upload $fileClass' type='file' name='{$documentName}-files[]' $multipleString $options>";
				$this->html .= "<div style='width:100%; display: flex;'>";
					if(is_numeric($this->userId)){
						$this->html .= "<input type='hidden' class='no-reset' class='no-reset' name='fileupload[user-id]' 			value='{$this->userId}'>";
					}
					if(!empty($targetDir)){
						$targetDir	= str_replace('\\', '/', $targetDir);
						$this->html .= "<input type='hidden' class='no-reset' class='no-reset' name='fileupload[targetDir]' 		value='{$targetDir}'>";
					}
					if(!empty($this->metakey)){
						$this->html .= "<input type='hidden' class='no-reset' class='no-reset' name='fileupload[metakey]' 		value='{$this->metakey}'>";
						$this->html .= "<input type='hidden' class='no-reset' class='no-reset' name='fileupload[metakey-index]' 	value='$documentName'>";
					}
					if(!empty($this->library)){
						$this->html .= "<input type='hidden' class='no-reset' class='no-reset' name='fileupload[library]' 		value='{$this->library}'>";
					}
					if(!empty($this->callback)){
						$this->html .= "<input type='hidden' class='no-reset' class='no-reset' name='fileupload[callback]' 		value='{$this->callback}'>";
					}

					$this->html .= "<input type='hidden' class='no-reset' class='no-reset' name='fileupload[updatemeta]' 		value='{$this->updatemeta}'>";
		
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
	public function documentPreview($documentPath, $index){
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
		
		$this->html .= "<div class='document'>";
			$this->html .= "<input type='hidden' class='no-reset' class='no-reset' name='{$this->metakey}[]' value='$metaValue'>";

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
		
		$this->html .= "<button type='button' class='remove-document button' data-url='$documentPath' data-user-id='{$this->userId}' data-metakey='$metakeyString' $libraryString>X</button>";
		$this->html .= "</div>";

		return true;
	}
}
