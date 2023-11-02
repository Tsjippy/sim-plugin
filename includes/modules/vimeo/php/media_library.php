<?php
namespace SIM\VIMEO;
use SIM;

// upload, download or edit a vimeo video
add_action( 'edit_attachment', function($attachmentId){
	if(empty($_REQUEST['changes'])){
		return;
	}

	$vimeoApi	= new VimeoApi();

	// Upload local video to vimeo
    if ( isset( $_REQUEST['attachments'][$attachmentId]['vimeo'] ) ) {
        $vimeoApi->upload($attachmentId);
    }

	// download vimeo video to server
	if ( !empty( $_REQUEST['attachments'][$attachmentId]['vimeo_url'] ) ) {
		$vimeoApi->downloadFromVimeo($_REQUEST['attachments'][$attachmentId]['vimeo_url'], $attachmentId);
	}

	// Update vimeo meta data
	$title			= $_REQUEST['changes']['title'];
	$description	= $_REQUEST['changes']['description'];
	$data			= [];
	if(!empty($title)){
		$data['name']	= $title;
	}
	if(!empty($description)){
		$data['description']	= $description;
	}

	if(!empty($data)){
		$vimeoApi->updateMeta($attachmentId, $data);
	}
} );

// Add upload to vimeo button to attachment page if auto upload is not on
add_filter( 'attachment_fields_to_edit', function($formFields, $post ){
	//only work on video's
	if(explode('/',$post->post_mime_type)[0] != 'video'){
		return $formFields;
	}

    $startTime = microtime(true);

	$vimeoId = get_post_meta( $post->ID, 'vimeo_id', true );

	if(is_numeric($vimeoId)){
		//check if backup already exists
		$path   = get_post_meta($post->ID, 'video_path', true);

		if(!file_exists($path)){
			$formFields['vimeo_url'] = array(
				'label' => "Video url",
				'input' => 'text',
				'value' => '',
				'helps' => "Enter the url to download a backup to your server (get it from <a href='https://vimeo.com/manage/$vimeoId/advanced' target='_blank'>this page</a>)"
			);
		}
	}elseif( !SIM\getModuleOption(MODULE_SLUG, 'upload') ){
		//Check if already uploaded
		$html    = "<div>";
			$html   .= "<input style='width: initial' type='checkbox' name='attachments[{$post->ID}][vimeo]' value='upload'>";
		$html   .= "</div>";

		$formFields['visibility'] = array(
			'value' => 'upload',
			'label' => __( 'Upload this video to vimeo' ),
			'input' => 'html',
			'html'  =>  $html
		);
	}

    $executionTime = (microtime(true) - $startTime);
	if($executionTime > 0.01){
		SIM\printArray(" Execution time of script = $executionTime sec");
	}

	return $formFields;
}, 10, 2);