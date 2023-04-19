<?php
namespace SIM\VIMEO;
use SIM;

// Delete video from vimeo when attachemnt is deleted, if that option is enabled
if(SIM\getModuleOption(MODULE_SLUG, 'remove')){
	add_action( 'delete_attachment', function($postId, $post ){

		if(explode('/', $post->post_mime_type)[0] == 'video'){
			$vimeoApi = new VimeoApi();
			$vimeoApi->deleteVimeoVideo($postId);
		}
	}, 10, 2);
}

add_action('sim_before_visibility_change', function($attachmentId, $visibility){

	if($visibility == 'private'){
		$vimeoApi	= new VimeoApi();
		$vimeoApi->hideVimeoVideo($attachmentId);
	}
}, 10, 2);

//change the url of vimeo videos so it points to vimeo.com
add_filter( 'wp_get_attachment_url', function( $url, $attId ) {
	$vimeoId   = get_post_meta($attId, 'vimeo_id', true);

    if(is_numeric($vimeoId)){
		$vimeoApi	= new VimeoApi();

		$video		= $vimeoApi->getVideo($vimeoId);

		if(isset($video['player_embed_url'])){
			$url	= $video['player_embed_url'];
		}
    }
	
    return $url;
}, 999, 2 );


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

// Uploads a video to vimeo, runs after file has been uploaded to the tmp folder but before adding it to the library
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

//change vimeo thumbnails
add_filter( 'wp_mime_type_icon', function ($icon, $mime, $postId) {

	if(strpos($icon, 'video.png') && is_numeric(get_post_meta($postId, 'vimeo_id', true))){
		$startTime = microtime(true);
		try{
			$path  = get_post_meta($postId, 'thumbnail', true);

			if(!file_exists($path)){
                $vimeoApi	= new VimeoApi();
				$path		= $vimeoApi->getThumbnail($postId);
				if(!$path) {
					return $icon;
				}
            }
			
			$newIcon		= SIM\pathToUrl($path);

			if(!$newIcon){
				return $icon;
			}

			$executionTime = (microtime(true) - $startTime);
			if($executionTime > 0.01){
				SIM\printArray(" Execution time of script = $executionTime sec");
			}

			return $newIcon;
		}catch(\Exception $e){
			SIM\printArray($e);
		}
	}
	
	return $icon;
}, 10, 9 );



