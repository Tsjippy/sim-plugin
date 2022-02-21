<?php
namespace SIM;

use function Crontrol\enqueue_assets;

global $Modules;

// Add upload to vimeo button to attachment page if auto upload is not on
if(empty($Modules['vimeo']['upload'])){
	add_filter( 'attachment_fields_to_edit', function($form_fields, $post ){
		//only work on video's
		if(explode('/',$post->post_mime_type)[0] != 'video') return;

		$vimeo_id = get_post_meta( $post->ID, 'vimeo_id', true );

		//video already on vimeo
		if(is_numeric($vimeo_id)) return;

		$html    = "<div>";
			$html   .= "<input style='width: initial' type='checkbox' name='attachments[{$post->ID}][vimeo]' value='upload'>";
		$html   .= "</div>";


		$form_fields['visibility'] = array(
			'value' => 'upload',
			'label' => __( 'Upload this video to vimeo' ),
			'input' => 'html',
			'html'  =>  $html
		);  
		return $form_fields;
	},10,2);
}

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
} );

//shortcode to display vimeo video's
add_shortcode("vimeo_video",function ($atts){
	global $StyleVersion;
	// Load css
	wp_enqueue_style( 'vimeo_style', plugins_url('css/style.css', __DIR__), array(), $StyleVersion);

	ob_start();

	$vimeo_id	= $atts['id'];
	?>
	<div class='vimeo-embed-container'>
		<iframe src='https://player.vimeo.com/video/<?php echo $vimeo_id; ?>' frameborder='0' webkitAllowFullScreen mozallowfullscreen allowFullScreen></iframe>
	</div>
	<?php
	return ob_get_clean();
});

//auto upload via js if enabled
if(!empty($Modules['vimeo']['upload'])){
	//load js script to change media screen
	add_action( 'wp_enqueue_media', function(){
		global $LoaderImageURL;
		global $StyleVersion;
		wp_enqueue_script('tus', IncludesUrl.'/js/tus.min.js', [], $StyleVersion);
		wp_enqueue_script('simnigeria_media_script', IncludesUrl.'/modules/vimeo/js/vimeo.js', ['tus','simnigeria_forms_script','media-audiovideo', 'sweetalert'], $StyleVersion);
		wp_localize_script('simnigeria_media_script', 
			'media_vars', 
			array( 
				'loading_gif' 	=> $LoaderImageURL,
				'ajax_url' 		=> admin_url( 'admin-ajax.php' ), 
			) 
		);
	});
}

// Delete video from vimeo when attachemnt is deleted, if that option is enabled
if(!empty($Modules['vimeo']['remove'])){
	add_action( 'delete_attachment', function($post_id, $post ){
		if(explode('/', $post->post_mime_type)[0] == 'video'){
			$VimeoApi = new VimeoApi();
			$VimeoApi->delete_vimeo_video($post_id);
		}
	},10,2);
}

add_action('before_visibility_change', function($attachment_id, $visibility){
	if($visibility == 'private'){
		$VimeoApi	= new VimeoApi();
		$VimeoApi->hide_vimeo_video($attachment_id);
	}
}, 10, 2);

//auto sync if that option is enabled
if(!empty($Modules['vimeo']['sync'])){
	//add scheduled task to sync vimeo library
	add_action('init',function(){
		add_action( 'sync_vimeo_action', "SIM\\vimeo_sync");
	});
	schedule_task('sync_vimeo_action', 'daily');

	//sync local db with vimeo.com
	function vimeo_sync(){
		$VimeoApi	= new VimeoApi();
		
		if ( $VimeoApi->is_connected() ) {
			$vimeo_videos	= $VimeoApi->get_uploaded_videos();
			$args = array(
				'post_type'  	=> 'attachment',
				'numberposts'	=> -1,
				'meta_query'	=> array(
					array(
						'key'   => 'vimeo_id'
					)
				)
			);
			$posts = get_posts( $args );

			$local_videos	= [];
			$online_videos	= [];

			//Build the local videos array
			foreach($posts as $post){
				$vimeo_id	= get_post_meta($post->ID, 'vimeo_id',true);
				if(is_numeric($vimeo_id)){
					$local_videos[$vimeo_id]	= $post->ID;
				}
			}

			//Build online video's array
			foreach($vimeo_videos as $vimeo_video){
				$vimeo_id					= str_replace('/videos/', '', $vimeo_video['uri']);
				$online_videos[$vimeo_id]	= html_entity_decode($vimeo_video['name']);
			}

			//remove any local video which does not exist on vimeo
			foreach(array_diff_key($local_videos, $online_videos) as $post_id){
				wp_delete_post($post_id);
			}

			//add any video which does not exist locally
			foreach(array_diff_key($online_videos, $local_videos) as $vimeo_id => $video_name){
				$VimeoApi->create_vimeo_post( $video_name, 'video/mp4', $vimeo_id);
			}
		}
	}
}

//add scheduled task to sync vimeo library
add_action('init',function(){
	add_action( 'create_vimeo_thumbnails', "SIM\\create_vimeo_thumbnails");
});
schedule_task('create_vimeo_thumbnails', 'daily');

//create local thumbnails
function create_vimeo_thumbnails(){
	$args = array(
		'post_type'  	=> 'attachment',
		'numberposts'	=> -1,
		'meta_query'	=> array(
			array(
				'key'   => 'vimeo_id'
			),
			array(
                'key' => 'thumbnail',
                'compare' => 'NOT EXISTS'
            )
		)
	);
	$posts = get_posts( $args );

	if(!empty($posts)){
		$VimeoApi	= new VimeoApi();
		foreach($posts as $post){
			$VimeoApi->get_thumbnail($post->ID);
		}
	}
}

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
		add_post_meta($attachment_id, '_wp_attached_file', $title);
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
			print_array($path);
			$icon		= path_to_url($path);
			print_array($icon);
		}catch(\Exception $e){
			print_array($e);
		}
	}
	
	return $icon;
}, 10, 9 );

//create a video on vimeo to upload to
add_action('wp_ajax_prepare-vimeo-upload', function(){
	global $wpdb;

	$file_name	= $_POST['file_name'];
	if(empty($file_name)) wp_die('No filename given', 500);

	$mime		= $_POST['file_type'];
	if(empty($mime)) wp_die('No file type given', 500);

	$start_position	= 0;
	$url			= '';
	$post_id		= 0;

	//check if post already exists
	$results = $wpdb->get_results($wpdb->prepare( "SELECT * FROM $wpdb->postmeta WHERE meta_key = 'vimeo_upload_data'"));
	foreach($results as $result){
		$data	= unserialize($result->meta_value);
		if($data['filename'] == $file_name){
			$url			= $data['url'];
			$post_id		= $result->post_id;
			$start_position = get_post_meta($post_id, 'vimeo_upload_position', true);
		}
	}

	if(empty($url)){
		$VimeoApi	= new VimeoApi();
		$result	= $VimeoApi->create_vimeo_video($file_name, $mime);
	}else{
		$result = [
			'upload_link'	=> $url,
			'post_id'		=> $post_id
		];
	}

	wp_die(
		wp_json_encode($result)
	);
}); 

//After upload to vimeo was succesfull
add_action('wp_ajax_add-uploaded-vimeo', function(){
	$post_id		= $_POST['post_id'];
	if(!is_numeric($post_id)) wp_die('No post id given', 500);

	$attachment = wp_prepare_attachment_for_js( $post_id );
	if ( ! $attachment ) {
		wp_die();
	}
	$attachment['icon']	= str_replace('default', 'video', $attachment['icon']);

	update_post_meta($post_id, '_wp_attached_file', 'Video on vimeo');

	// Download an backup
	$VimeoApi	= new VimeoApi();
	$vimeo_id   = $VimeoApi->download_video($post_id);
	
	//$video = new VideoController;
	//$url =$video->getVimeoDirectUrl('http://vimeo.com/'.$vimeo_id);

	//$VimeoApi->download_from_vimeo('https://i.vimeocdn.com/video/740773266-9491d60ada4ee1df38dd2e70f58bc19bcd69ea390e6868b836c40e0a099ccd50-d?mw=2900&mh=1631&q=70', $vimeo_id);

	delete_post_meta($post_id, 'vimeo_upload_link');

	echo wp_json_encode(
		array(
			'success' => true,
			'data'    => $attachment,
		)
	);

	wp_die();
});



if(!empty($Modules['vimeo']['upload'])){
	//add filter
	add_action('post-html-upload-ui', function(){
		add_filter('gettext', 'SIM\change_upload_size_message', 10, 3);
	});

	//add filter
	add_action('post-plupload-upload-ui', function(){
		add_filter('gettext', 'SIM\change_upload_size_message', 10, 3);
	});

	//do the filter: change upload size message
	function change_upload_size_message($translation, $text, $domain){
		if($text == "Maximum upload file size: %s."){
			$translation	= "Maximum upload file size: %s, unlimited upload size for videos.";
		}

		return $translation;
	}

	//remove filter
	add_action('post-upload-ui', function(){
		remove_filter( 'gettext', 'SIM\change_upload_size_message', 10 );
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
