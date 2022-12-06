<?php
namespace SIM\VIMEO;
use SIM;
use WP_Error;
use WP_User;

add_action( 'rest_api_init', function () {

	// Clean backup dir
	register_rest_route(
		RESTAPIPREFIX.'/vimeo',
		'/cleanup_backup',
		array(
			'methods' 				=> 'POST',
			'callback' 				=> 	__NAMESPACE__.'\cleanupBackupFolder',
			'permission_callback' 	=> '__return_true'
		)
	);

	// prepare video upload
	register_rest_route(
		RESTAPIPREFIX.'/vimeo',
		'/prepare_vimeo_upload',
		array(
			'methods' 				=> 'POST',
			'callback' 				=> 	__NAMESPACE__.'\prepareVimeoUpload',
			'permission_callback' 	=> '__return_true',
			'args'					=> array(
				'file_name'		=> array(
					'required'	=> true
                ),
                'file_type'		=> array(
					'required'	=> true
				)
			)
		)
	);

    // Save uploaded video details
    register_rest_route(
		RESTAPIPREFIX.'/vimeo',
		'/add_uploaded_vimeo',
		array(
			'methods' 				=> 'POST',
			'callback' 				=> 	__NAMESPACE__.'\addUploadedVimeo',
			'permission_callback' 	=> '__return_true',
			'args'					=> array(
				'post_id'		=> array(
					'required'	=> true,
					'validate_callback' => function($postId){
						return is_numeric($postId);
					}
                )
			)
		)
	);

	// Save uploaded video details
    register_rest_route(
		RESTAPIPREFIX.'/vimeo',
		'/download_to_server',
		array(
			'methods' 				=> 'POST,GET',
			'callback' 				=> 	function(){
				$vimeo		= new VimeoApi();
				$vimeoId	= $_POST['vimeoid'];

				$post	= $vimeo->getPost($vimeoId);
				if(is_wp_error($post)){
					return $post;
				}else{
					$result		= $vimeo->downloadFromVimeo($_POST['download_url'], $post->ID);
					if(is_wp_error($result)){
						return $result;
					}
					return "Video downloaded to server succesfully";
				}
			},
			'permission_callback' 	=> '__return_true',
			'args'					=> array(
				'vimeoid'		=> array(
					'required'	=> true,
					'validate_callback' => function($vimeoId){
						return is_numeric($vimeoId);
					}
                ),
				'download_url'		=> array(
					'required'	=> true
                )
			)
		)
	);
});

/**
 * create a video on vimeo to upload to
 *
 * @return	array		arra containing the upload link, post id and vimeo id
 *
 */
function prepareVimeoUpload(){
	global $wpdb;

	$fileName	= $_POST['file_name'];
	$mime		= $_POST['file_type'];

	$url		= '';
	$postId		= 0;

	//check if post already exists
	$results = $wpdb->get_results($wpdb->prepare( "SELECT * FROM $wpdb->postmeta WHERE meta_key = 'vimeo_upload_data'"));
	foreach($results as $result){
		$data	= unserialize($result->meta_value);
		if($data['filename'] == $fileName){
			$url			= $data['url'];
			$postId			= $result->post_id;
			$vimeoId      	= $data['vimeo_id'];
		}
	}

    // If there is no file with the same name, create a new video on vimeo
	if(empty($url)){
		$VimeoApi	= new VimeoApi();
		$result		= $VimeoApi->createVimeoVideo($fileName, $mime);
    // Return a previous created vimeo video link and post id
	}else{
		$result = [
			'upload_link'	=> $url,
			'post_id'		=> $postId,
			'vimeo_id'      => $vimeoId
		];
	}

	return $result;
}

/**
 * After upload to vimeo was succesfull
 *
 * @return	array with succes and the attachment data
 */
function addUploadedVimeo(){
	$postId		= $_POST['post_id'];

    // Get the attachement data
	$attachment = wp_prepare_attachment_for_js( $postId );
	if ( ! $attachment ) {
		return new WP_Error('attachemnt', 'Something went wrong');
	}
    // Replace the icon if needed
	$attachment['icon']	= str_replace('default', 'video', $attachment['icon']);

	update_post_meta($postId, '_wp_attached_file', $attachment['title']);

    // remove upload data
    delete_post_meta($postId, 'vimeo_upload_data');

	// Download a backup or send an e-mail if that is not possible
	$vimeoApi	= new VimeoApi();
	$vimeoApi->downloadVideo($postId);

    // Media libray expects the below array!
    return [
        'success' => true,
        'data'    => $attachment
    ];
	return [
        'response' => [
            'success' => true,
            'data'    => $attachment
        ]
    ];
}

function cleanupBackupFolder(){
	$vimeoApi	= new VimeoApi();

	$vimeoVideos	= $vimeoApi->getUploadedVideos();
	if(!$vimeoVideos){
		return;
	}

	//Build online video's array
	foreach($vimeoVideos as $vimeoVideo){
		$vimeoId				= str_replace('/videos/', '', $vimeoVideo['uri']);
		$onlineVideos[$vimeoId]	= html_entity_decode($vimeoVideo['name']);
	}

	// Remove any backup
	$files      = glob($vimeoApi->backupDir.'*.mp4');
	$count		= 0;
	foreach($files as $file){
		$vimeoId    = explode('_', basename($file))[0];

		if(!in_array($vimeoId, array_keys($onlineVideos))){
			unlink($file);
			$count++;
		}
	}

	if($count == 0){
		return 'There was nothing to remove';
	}

	return "Succesfully cleaned up the backup folder, removed $count files";
}