<?php
namespace SIM\VIMEO;
use SIM;
use WP_Error;
use WP_User;

add_action( 'rest_api_init', function () {
	// prepare video upload
	register_rest_route( 
		'sim/v1/vimeo', 
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
		'sim/v1/vimeo', 
		'/add_uploaded_vimeo', 
		array(
			'methods' 				=> 'POST',
			'callback' 				=> 	__NAMESPACE__.'\addUploadedVimeo',
			'permission_callback' 	=> '__return_true',
			'args'					=> array(
				'post_id'		=> array(
					'required'	=> true,
					'validate_callback' => 'is_numeric'
                )
			)
		)
	);

	// Save uploaded video details
    register_rest_route( 
		'sim/v1/vimeo', 
		'/download_to_server', 
		array(
			'methods' 				=> 'POST',
			'callback' 				=> 	function(){
				$vimeo		= new VimeoApi();
				$vimeoId	= $_POST['vimeoid'];

				// Get the post for this video
				$posts = get_posts(array(
					'numberposts'   => -1,
					'post_type'     => 'attachment',
					'meta_key'      => 'vimeo_id',
					'meta_value'    => $vimeoId
				));

				if(empty($posts)){
					return new WP_Error('vimeo'," No post found for this video");
				}else{
					$title	= $posts[0]->post_title;

					$result		= $vimeo->downloadFromVimeo($_POST['download_url'], $vimeoId."_$title");
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
					'validate_callback' => 'is_numeric'
                ),
				'download_url'		=> array(
					'required'	=> true
                )
			)
		)
	);
});

//create a video on vimeo to upload to
function prepareVimeoUpload(){
	global $wpdb;

	$file_name	= $_POST['file_name'];
	$mime		= $_POST['file_type'];

	$url			= '';
	$post_id		= 0;

	//check if post already exists
	$results = $wpdb->get_results($wpdb->prepare( "SELECT * FROM $wpdb->postmeta WHERE meta_key = 'vimeo_upload_data'"));
	foreach($results as $result){
		$data	= unserialize($result->meta_value);
		if($data['filename'] == $file_name){
			$url			= $data['url'];
			$post_id		= $result->post_id;
			$vimeo_id      	= $data['vimeo_id'];
		}
	}

    // If there is no file with the same name, create a new video on vimeo
	if(empty($url)){
		$VimeoApi	= new VimeoApi();
		$result	= $VimeoApi->createVimeoVideo($file_name, $mime);
    // Return a previous created vimeo video link and post id
	}else{
		$result = [
			'upload_link'	=> $url,
			'post_id'		=> $post_id,
			'vimeo_id'      => $vimeo_id
		];
	}

	return $result;
}

//After upload to vimeo was succesfull
function addUploadedVimeo(){
	$post_id		= $_POST['post_id'];

    // Get the attachement data
	$attachment = wp_prepare_attachment_for_js( $post_id );
	if ( ! $attachment ) {
		return new WP_Error('attachemnt', 'Something went wrong');
	}
    // Replace the icon if needed
	$attachment['icon']	= str_replace('default', 'video', $attachment['icon']);

	update_post_meta($post_id, '_wp_attached_file', $attachment['title']);

    // remove upload data
    delete_post_meta($post_id, 'vimeo_upload_data');

	// Download a backup or send an e-mail if that is not possible
	$VimeoApi	= new VimeoApi();
	$VimeoApi->downloadVideo($post_id);

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