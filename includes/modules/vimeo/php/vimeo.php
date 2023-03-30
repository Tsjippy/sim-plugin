<?php
namespace SIM\VIMEO;
use SIM;

// Add upload to vimeo button to attachment page if auto upload is not on
add_filter( 'attachment_fields_to_edit', function($formFields, $post ){
	//only work on video's
	if(explode('/',$post->post_mime_type)[0] != 'video'){
		return $formFields;
	}

	$vimeoId = get_post_meta( $post->ID, 'vimeo_id', true );

	//Check if already uploaded
	if(!SIM\getModuleOption(MODULE_SLUG, 'upload') && !is_numeric($vimeoId)){
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

	//check if backup already exists
	$vimeo	= new VimeoApi();
	$path	= $vimeo->getVideoPath($post->ID);
	if(is_numeric($vimeoId) && !file_exists($path)){
		$formFields['vimeo_url'] = array(
			'label' => "Video url",
			'input' => 'text',
			'value' => '',
			'helps' => "Enter the url to download a backup to your server (get it from <a href='https://vimeo.com/manage/$vimeoId/advanced' target='_blank'>this page</a>)"
		);
	}

	return $formFields;
},10,2);

//process the request to upload to Vimeo
add_action( 'edit_attachment', function($attachmentId){
	$vimeo	= new VimeoApi();

	// Upload local video to vimeo
    if ( isset( $_REQUEST['attachments'][$attachmentId]['vimeo'] ) ) {
        $vimeo->upload($attachmentId);
    }

	// download vimeo video to server
	if ( !empty( $_REQUEST['attachments'][$attachmentId]['vimeo_url'] ) ) {
		$vimeo->downloadFromVimeo($_REQUEST['attachments'][$attachmentId]['vimeo_url'], $attachmentId);
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
		$vimeo->updateMeta($attachmentId, $data);
	}
} );

// Delete video from vimeo when attachemnt is deleted, if that option is enabled
if(SIM\getModuleOption(MODULE_SLUG, 'remove')){
	add_action( 'delete_attachment', function($postId, $post ){
		if(explode('/', $post->post_mime_type)[0] == 'video'){
			$VimeoApi = new VimeoApi();
			$VimeoApi->deleteVimeoVideo($postId);
		}
	},10,2);
}

add_action('sim_before_visibility_change', function($attachment_id, $visibility){
	if($visibility == 'private'){
		$vimeoApi	= new VimeoApi();
		$vimeoApi->hideVimeoVideo($attachment_id);
	}
}, 10, 2);

//change the url of vimeo videos so it points to vimeo.com
add_filter( 'wp_get_attachment_url', function( $url, $attId ) {
    $vimeoId   = get_post_meta($attId, 'vimeo_id', true);
    if(is_numeric($vimeoId)){
        $url    = "https://vimeo.com/$vimeoId";
    }
    return $url;
}, 999, 2 );

//change hyperlink to shortcode for vimeo videos
add_filter( 'media_send_to_editor', function ($html, $id, $attachment) {
	if(strpos($attachment['url'], 'https://vimeo.com') !== false){
		$vimeoId	= str_replace('https://vimeo.com/', '', $attachment['url']);

		/* $html = '<!-- wp:video {"id":'.$attachment['id'].'} -->';
			$html .= '<figure class="wp-block-video"><video src="https://vimeo.com/'.$vimeo_id.'" controls="controls" width="300" height="150"></video></figure>';
		$html .= '<!-- /wp:video -->'; */

		$html	= "[vimeo_video id=$vimeoId]";
	}
	
	return $html;
}, 10, 9 );

//change vimeo thumbnails
add_filter( 'wp_mime_type_icon', function ($icon, $mime, $postId) {
	if(strpos($icon, 'video.png')){
		try{
			$vimeoApi	= new VimeoApi();
			$path		= $vimeoApi->getThumbnail($postId);
			if(!$path) {
				return $icon;
			}
			$icon		= SIM\pathToUrl($path);
		}catch(\Exception $e){
			SIM\printArray($e);
		}
	}
	
	return $icon;
}, 10, 9 );

if(SIM\getModuleOption(MODULE_SLUG, 'upload')){
	//add filter
	add_action('post-html-upload-ui', function(){
		add_filter('gettext', __NAMESPACE__.'\changeUploadSizeMessage', 10, 2);
	});

	//add filter
	add_action('post-plupload-upload-ui', function(){
		add_filter('gettext', __NAMESPACE__.'\changeUploadSizeMessage', 10, 2);
	});

	//do the filter: change upload size message
	function changeUploadSizeMessage($translation, $text){
		if($text == "Maximum upload file size: %s."){
			$translation	= "Maximum upload file size: %s, unlimited upload size for videos.";
		}

		return $translation;
	}

	//remove filter
	add_action('post-upload-ui', function(){
		remove_filter( 'gettext', 'SIM\VIMEO\change_upload_size_message', 10 );
	});
}

//change the default output for a local video to a vimeo iframe
add_filter( 'render_block', function( $blockContent,  $block ){
	//if this is a video block
	if($block['blockName'] == 'core/video'){
		$postId		= $block['attrs']['id'];
		$vimeoId	= get_post_meta($postId, 'vimeo_id', true);

		//if this video is an vimeo video
		if(is_numeric($vimeoId)){
			//return a vimeo block
			ob_start();
			?>
			<figure class="wp-block-embed is-type-video is-provider-vimeo wp-block-embed-vimeo wp-embed-aspect-16-9 wp-has-aspect-ratio">
				<div class="wp-block-embed__wrapper">
					<iframe loading='lazy' src="https://player.vimeo.com/video/668529102?h=df63ad659d&amp;dnt=1&amp;app_id=122963" width="915" height="515" frameborder="0" allow="autoplay; fullscreen; picture-in-picture" allowfullscreen>
					</iframe>
				</div>
			</figure>
			<?php

            return ob_get_clean();
        }
	}

	return $blockContent;
}, 10, 2);

// Runs after file has been uploaded to the tmp folder but before adding it to the library
add_filter( 'wp_handle_upload', function($file){

	if(explode('/', $file['type'])[0] == 'video' && is_numeric($_REQUEST['post'])){
		$vimeoApi	= new VimeoApi();
		$postId		= $_REQUEST['post'];

		try{
			$post		= get_post($postId);

			if(!empty($post->post_title)){
				$name	= $post->post_title;
			}else{
				$name	= basename($file['file']);
			}

			if(!empty($post->post_content)){
				$content	= $post->post_content;
			}else{
				$content	= $name;
			}

			$response 	= $vimeoApi->api->upload($file['file'], [
				'name'          => $name,
				'description'   => $content
			]);

			$vimeoId	= str_replace('/videos/', '', $response);

			if(!is_numeric($vimeoId)){
				return $file;
			}

			$path       = $vimeoApi->backupDir;

			$filename   = $vimeoId."_".get_the_title($postId);

			$filePath  = str_replace('\\', '/', $path.$filename.'.mp4');

			move_uploaded_file($file['file'], $filePath);

			$vimeoApi->saveVideoPath($postId, $filePath);

			update_post_meta($post->ID, 'vimeo_id', $vimeoId);

		}catch(\Exception $e) {
			SIM\printArray('Unable to upload: '.$e->getMessage());
		}
	}

	return $file;
} );