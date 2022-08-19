<?php
namespace SIM\FILEUPLOAD;
use SIM;

//Make upload_files function availbale for AJAX request
add_action ( 'wp_ajax_upload_files', function (){
	if (empty($_FILES["files"])) {
		// Set http header error
		header('HTTP/1.0 422 Unprocessable Entity');
		// Return error message
		die(json_encode(array('error' => 'No files found')));
	}

	$fileUploader	= new FileUploader($_POST, $_FILES["files"]);

	echo json_encode($fileUploader->filesArr);
	wp_die();
});