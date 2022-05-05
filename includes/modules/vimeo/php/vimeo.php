<?php
namespace SIM\VIMEO;
use SIM;

// Add upload to vimeo button to attachment page if auto upload is not on
add_filter( 'attachment_fields_to_edit', function($form_fields, $post ){
	//only work on video's
	if(explode('/',$post->post_mime_type)[0] != 'video') return $form_fields;

	$vimeo_id = get_post_meta( $post->ID, 'vimeo_id', true );

	//Check if already uploaded
	if(!SIM\get_module_option('vimeo', 'upload')){
		//video already on vimeo
		if(!is_numeric($vimeo_id)){
			$html    = "<div>";
				$html   .= "<input style='width: initial' type='checkbox' name='attachments[{$post->ID}][vimeo]' value='upload'>";
			$html   .= "</div>";

			$form_fields['visibility'] = array(
				'value' => 'upload',
				'label' => __( 'Upload this video to vimeo' ),
				'input' => 'html',
				'html'  =>  $html
			);  
		}
	}

	//check if backup already exists
	if(is_numeric($vimeo_id) and !file_exists(WP_CONTENT_DIR."/vimeo_files/backup/$vimeo_id.mp4")){
		$form_fields['vimeo_url'] = array(
			'label' => "Video url",
			'input' => 'text',
			'value' => '',
			'helps' => "Enter the url to download a backup to your server (get it from <a href='https://vimeo.com/manage/$vimeo_id/advanced' target='_blank'>this page</a>)"
		);
	}

	return $form_fields;
},10,2);

//process the request to upload to Vimeo
add_action( 'edit_attachment', function($attachment_id){
    if ( isset( $_REQUEST['attachments'][$attachment_id]['vimeo'] ) ) {
        //check if changed
        $vimeo_id   = get_post_meta( $attachment_id, 'vimeo_id',true);

        if(!is_numeric($vimeo_id)){
			$VimeoApi	= new VimeoApi();

			$VimeoApi->upload($attachment_id);
        }
    }

	if ( !empty( $_REQUEST['attachments'][$attachment_id]['vimeo_url'] ) ) {
		$vimeo		= new VimeoApi();
		$vimeo_id   = get_post_meta( $attachment_id, 'vimeo_id',true);
		$vimeo->downloadFromVimeo($_REQUEST['attachments'][$attachment_id]['vimeo_url'], $vimeo_id);
	}
} );

// Delete video from vimeo when attachemnt is deleted, if that option is enabled
if(SIM\get_module_option('vimeo', 'remove')){
	add_action( 'delete_attachment', function($postId, $post ){
		if(explode('/', $post->post_mime_type)[0] == 'video'){
			$VimeoApi = new VimeoApi();
			$VimeoApi->deleteVimeoVideo($postId);
		}
	},10,2);
}

add_action('before_visibility_change', function($attachment_id, $visibility){
	if($visibility == 'private'){
		$VimeoApi	= new VimeoApi();
		$VimeoApi->hide_vimeo_video($attachment_id);
	}
}, 10, 2);

//change the url of vimeo videos so it points to vimeo.com
add_filter( 'wp_get_attachment_url', function( $url, $att_id ) {
    $vimeo_id   = get_post_meta($att_id, 'vimeo_id', true);
    if(is_numeric($vimeo_id)){
        $url    = "https://vimeo.com/$vimeo_id";
    }
    return $url;
}, 999, 2 );

//change hyperlink to shortcode for vimeo videos
add_filter( 'media_send_to_editor', function ($html, $id, $attachment) {
	if(strpos($attachment['url'], 'https://vimeo.com') !== false){
		$vimeo_id	= str_replace('https://vimeo.com/','',$attachment['url']);

		/* $html = '<!-- wp:video {"id":'.$attachment['id'].'} -->';
			$html .= '<figure class="wp-block-video"><video src="https://vimeo.com/'.$vimeo_id.'" controls="controls" width="300" height="150"></video></figure>';
		$html .= '<!-- /wp:video -->'; */

		$html	= "[vimeo_video id=$vimeo_id]";
	}
	
	return $html;
}, 10, 9 );

//if video meta changes, change on vimeo as well
add_action( 'edit_attachment', function($attachment_id){	
	$title			= $_REQUEST['changes']['title'];
	$description	= $_REQUEST['changes']['description'];
	$data			= [];
	if(!empty($title)){
		$data['name']	= $title;
		update_post_meta($attachment_id, '_wp_attached_file', $title);
	}
	if(!empty($description)){
		$data['description']	= $description;
	}

	if(!empty($data)){
		$VimeoApi		= new VimeoApi();
		$VimeoApi->update_meta($attachment_id, $data);
	}
});

//change vimeo thumbnails
add_filter( 'wp_mime_type_icon', function ($icon, $mime, $post_id) {
	if(strpos($icon, 'video.png')){
		try{
			$VimeoApi	= new VimeoApi();
			$path		= $VimeoApi->get_thumbnail($post_id);
			if(!$path)  return $icon;
			$icon		= SIM\path_to_url($path);
		}catch(\Exception $e){
			SIM\print_array($e);
		}
	}
	
	return $icon;
}, 10, 9 );

if(SIM\get_module_option('vimeo', 'upload')){
	//add filter
	add_action('post-html-upload-ui', function(){
		add_filter('gettext', 'SIM\VIMEO\change_upload_size_message', 10, 2);
	});

	//add filter
	add_action('post-plupload-upload-ui', function(){
		add_filter('gettext', 'SIM\VIMEO\change_upload_size_message', 10, 2);
	});

	//do the filter: change upload size message
	function change_upload_size_message($translation, $text){
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
add_filter( 'render_block', function( $block_content,  $block ){
	//if this is a video block
	if($block['blockName'] == 'core/video'){
		$post_id	= $block['attrs']['id'];
		$vimeo_id	= get_post_meta($post_id, 'vimeo_id', true);

		//if this video is an vimeo video
		if(is_numeric($vimeo_id)){
			//return a vimeo block
			ob_start();
			?>
			<figure class="wp-block-embed is-type-video is-provider-vimeo wp-block-embed-vimeo wp-embed-aspect-16-9 wp-has-aspect-ratio">
				<div class="wp-block-embed__wrapper">
					<iframe src="https://player.vimeo.com/video/668529102?h=df63ad659d&amp;dnt=1&amp;app_id=122963" width="915" height="515" frameborder="0" allow="autoplay; fullscreen; picture-in-picture" allowfullscreen>
					</iframe>
				</div>
			</figure>
			<?php

            return ob_get_clean();
        }
	}

	return $block_content;
},10,2);
