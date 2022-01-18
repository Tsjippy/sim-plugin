<?php
namespace SIM;

//load js script to change media screen
add_action( 'wp_enqueue_media', function(){
	global $LoaderImageURL;
	global $StyleVersion;
	wp_enqueue_script('tus', IncludesUrl.'/js/tus.min.js', [], $StyleVersion);
	wp_enqueue_script('simnigeria_media_script', IncludesUrl.'/js/media_library.js', ['tus','simnigeria_forms_script','media-audiovideo', 'sweetalert'], $StyleVersion);
	wp_localize_script( 'simnigeria_media_script', 
		'media_vars', 
		array( 
			'loading_gif' 	=> $LoaderImageURL,
			'ajax_url' 		=> admin_url( 'admin-ajax.php' ), 
		) 
	);
});

//remove_the default vimeo media submenu
add_action( 'admin_menu', 'SIM\disable_category_menu',99);
function disable_category_menu () {
	//remove the old one
	remove_submenu_page("upload.php","dgv-library");
	//add a new one
	add_media_page(
		__( 'WP Vimeo Library', 'wp-vimeo-videos' ),
		'Vimeo',
		'upload_files',
		'simnigeria_add_vimeo',
		'SIM\add_vimeo_media_menu_output'
	);
}

add_action('wp_ajax_delete_vimeo', function(){
	$vimeo_id	= $_POST['vimeo_id'];
	if(!is_numeric($vimeo_id)) wp_die('Invalid vimeo id', 500);

	if(!is_user_logged_in()) wp_die('no permission', 500);

	$db_helper		= new \WP_DGV_Db_Helper();
	$post_id		= $db_helper->get_post_id($vimeo_id);
	$result			= delete_vimeo_video($post_id);

	if($result){
		wp_die('Successfully deleted the video from Vimeo');
	}else{
		wp_die("Error: $result", 500);
	}
});

function delete_vimeo_video($post_id){
	$vimeo_helper	= new \WP_DGV_Api_Helper();
	$db_helper		= new \WP_DGV_Db_Helper();

	$result			= false;

	if ( $vimeo_helper->is_connected ) {
		//Get the vimeo id
		$vimeo_id = $db_helper->get_vimeo_id( $post_id );

		//Remove the wp post
		$result = wp_delete_post($post_id, true);

		//Deleting video on vimeo
		$vimeo_result = $vimeo_helper->delete("/videos/$vimeo_id");

		if(isset($vimeo_result['body']['error'])){
			$result	=$vimeo_result['body']['error'];
		}
	}

	return $result;
}
//Render the output of the new menu item
function add_vimeo_media_menu_output(){
	ob_start();
	if ( isset( $_GET['action'] ) && isset( $_GET['id'] )&& $_GET['action'] === 'delete' ) {
		$result	= delete_vimeo_video($_GET['id']);

		//Showing the result
		if($result){
			?>
			<style>
			.notice-success {
				background-color: #dff0d8;
				border: 1px solid #d6e9c6;
				color: #3c763d;
				padding: 10px;
				margin: 10px 0 20px 0;
			}
			</style>

			<div class="notice-success">
				<p>Video removed successfully.</p>
			</div>
			<?php
		}else{
			?>
			<style>
			.notice-error {
				background-color: #f2dede;
				color: #a94442;
				border: 1px solid #ebccd1;
				margin: 10px 10px 20px;
				padding: 10px;
				-webkit-border-radius: 3px;
				-moz-border-radius: 3px;
				border-radius: 3px;
				font-size: 13px;
			}
			</style>
			<div class='notice-error'>
				<p>
			<?php
			echo $result;
			echo "</p></div>";
		}
	}
			
	?>
	<h2><?php _e( 'Vimeo Videos', 'wp-vimeo-videos' ); ?></h2>

		<a href="<?php echo admin_url( 'upload.php?page=' . \WP_DGV_Admin::PAGE_VIMEO . '&action=new' ); ?>"
		   class="page-title-action button"><?php _e( 'Upload new', 'wp-vimeo-videos' ); ?></a>

		<?php if ( current_user_can( 'manage_options' ) ): ?>
			<a href="<?php echo admin_url( 'options-general.php?page=' . \WP_DGV_Admin::PAGE_SETTINGS . '&action=settings' ); ?>"
			   class="page-title-action button"><?php _e( 'Settings', 'wp-vimeo-videos' ); ?>
			</a>
		<?php endif; ?>	

	<form method="post">

		<input type="hidden" name="page" value="test_list_table">

		<?php

		$list_table = new List_Table();

		$list_table->prepare_items();

		//$list_table->search_box( 'search', 'search_id' ); //TODO

		$list_table->display();

		?>

	</form>

	<?php

	echo ob_get_clean();
}

if ( ! class_exists( 'WP_DGV_List_Table' ) ) {
	if(!file_exists(ABSPATH . 'wp-content/plugins/wp-vimeo-videos/includes/class-wp-dgv-list-table.php')) return;
	
	require_once ABSPATH . 'wp-content/plugins/wp-vimeo-videos/includes/class-wp-dgv-list-table.php';
	
}

/**
 * The videos list table used to display all the videos
 *
 * @since      1.0.0
 * @package    WP_DGV
 * @subpackage WP_DGV/includes
 * @copyright  Darko Gjorgjijoski <info@codeverve.com>
 * @license    GPLv2
 */
class List_Table extends \WP_DGV_List_Table {
	/**
	 * Render the designation name column
	 *
	 * @param object $item
	 *
	 * @return string
	 */
	public function column_title( $item ) {
		$actions           	= array();
		$url               	= admin_url( 'upload.php?page=' . \WP_DGV_Admin::PAGE_VIMEO . '&action=edit&id=' . $item->ID );
		$vimeo_uri  		= $this->db_helper->get_vimeo_uri($item->ID);
		$vimeo_id   		= $this->db_helper->get_vimeo_id($item->ID);
		$vimeo_link       	= 'https://vimeo.com/'.$vimeo_id;
		$delete_url         = admin_url( 'upload.php?page=simnigeria_add_vimeo&action=delete&id=' . $item->ID );
		$actions['edit']   	= sprintf( '<a href="%s" data-id="%d" title="%s">%s</a>', $url, $item->ID, __( 'Manage this video', 'wp-vimeo-videos' ), __( 'Manage', 'wp-vimeo-videos' ) );
		$actions['vimeo'] 	= sprintf('<a href="%s" target="_blank" data-id="%d" title="%s">%s</a>', $vimeo_link, $item->ID, __('Vimeo video link', 'wp-vimeo-videos'), __('Vimeo Link', 'wp-vimeo-videos'));
		$actions['remove']  = sprintf( '<a href="%s" data-id="%d" title="%s">%s</a>', $delete_url, $item->ID, __( 'Delete this video', 'wp-vimeo-videos' ), __( 'Delete', 'wp-vimeo-videos' ) );

		return sprintf( '<a href="%1$s"><strong>%2$s</strong></a> %3$s', $url, $item->post_title, $this->row_actions( $actions ) );
	}
}

add_action( 'dgv_after_upload', '_dgv_after_uploadn', 10, 2 );
function _dgv_after_uploadn( $response, $api ) {
	//Hide the video from vimeo
	$uri = wvv_response_to_uri( $response );
	if ( ! empty( $uri ) ) {
		try {
			$response = $api->request( $uri, array(
				'privacy' => array(
					'view' => "disable"
				)
			), 'PATCH' );
		} catch ( \Exception $e ) {
			print_array( 'Hide Vimeo video: ' . $e->getMessage() );
		}
	} else {
		print_array( 'Hide Vimeo video: Video not found.');
	}
	
	wp_send_json_success( array(
		'message' => 'Video uploaded successfully.<br>I have already put the shortcode for the video, in the "Post Content" field for you.<br>This code will be replaced by the video on publish.<br>You can close this window now.',
	) );
}

//add scheduled task to sync vimeo library
add_action('init',function(){
	add_action( 'sync_vimeo_action', "SIM\\vimeo_sync");
});

schedule_task('sync_vimeo_action', 'daily');

//sync local db with vimeo.com
function vimeo_sync(){
	$vimeo_helper	= new \WP_DGV_Api_Helper();
	$db_helper		= new \WP_DGV_Db_Helper();
	
	if ( $vimeo_helper->is_connected ) {
		$online_videos	= $vimeo_helper->get_uploaded_videos();
		$local_videos	= $db_helper->get_videos();

		//Check if we need to remove any local video's
		foreach($local_videos as $local_video){
			$found	= false;

			//loop over the online videos, each entry is an array
			foreach($online_videos as $online_video){
				if(in_array($local_video->post_title, $online_video)){
					$found	= true;
					break;
				}
			}

			//local video is not found, remove it
			if(!$found){
				wp_delete_post($local_video->ID, true);
			}
		}

		//Check if we need to add any local video's
		foreach($online_videos as $online_video){
			$found	= false;

			//loop over the online videos, each entry is an array
			foreach($local_videos as $local_video){
				if(in_array($local_video->post_title, $online_video)){
					$found	= true;
					break;
				}
			}

			//local video is not found, add it
			if(!$found){
				$attachment_id	= $db_helper->create_local_video( $online_video['name'], $online_video['description'], str_replace('/videos/', '', $online_video['uri']), 'sync' );
				add_post_meta($attachment_id, 'vimeo_id', str_replace('/videos/', '', $online_video['uri']));
				update_post_meta($attachment_id, '_wp_attached_file', 'Video on vimeo');
				update_post_meta($attachment_id, 'dgv_response', $online_video['uri']);
			}
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

//upload a video to vimeo when uploaded
add_action( 'add_attachment', function($attachment_id ){
    $attachment = get_post($attachment_id);
    if(explode('/', $attachment->post_mime_type)[0] == 'video'){
        $vimeo_helper	= new \WP_DGV_Api_Helper();
        $db_helper		= new \WP_DGV_Db_Helper();
        $file_path      = get_attached_file($attachment_id);

        $args            = [
            'name'       => $attachment->post_title,
            'description' => $attachment->post_content,
        ];

        $result       = $vimeo_helper->upload( $file_path, $args);

        //upload succesful
        if($result['response']){
            //add to local vimeo database
            $db_helper->create_local_video( $attachment->post_title, $attachment->post_content, $result['response'], 'frontend' );

            //add to wp library
            $vimeo_id   = str_replace('/videos/','',$result['response']);
            add_post_meta($attachment_id, 'vimeo_id', $vimeo_id);
            update_post_meta($attachment_id, '_wp_attached_file', 'Video on vimeo');
			update_post_meta($attachment_id, 'dgv_response', $result['response']);

            //remove file
            unlink($file_path);
        }
    }
});

//change hyperlink to shortcode for vimeo videos
add_filter( 'media_send_to_editor', function ($html, $id, $attachment) {
	if(strpos($attachment['url'], 'https://vimeo.com') !== false){
		$vimeo_id	= str_replace('https://vimeo.com/','',$attachment['url']);
		$html = "[dgv_vimeo_video id='$vimeo_id']";
	}
	
	return $html;
}, 10, 9 );

add_action( 'edit_attachment', function($attachment_id){	
	$title			= $_REQUEST['changes']['title'];
	$description	= $_REQUEST['changes']['description'];
	$params			= [];
	if(!empty($title)){
		$params['name']	= $title;
	}
	if(!empty($description)){
		$params['description']	= $description;
	}

	if(!empty($params)){
		$vimeo_helper	= new \WP_DGV_Api_Helper();
		$db_helper		= new \WP_DGV_Db_Helper();
		$vimeo_uri 		= $db_helper->get_vimeo_uri( $_REQUEST['id'] );

		if ( $vimeo_helper->is_connected and !empty($vimeo_uri)) {
			$response = $vimeo_helper->api->request($vimeo_uri, $params, 'PATCH');
		}
	}
});

//change vimeo thumbnails
add_filter( 'wp_mime_type_icon', function ($icon, $mime, $post_id) {
	if(strpos($icon, 'video.png')){
		$vimeo_id	= get_post_meta($post_id, 'vimeo_id', true);
		if($vimeo_id){
			$vimeo_helper	= new \WP_DGV_Api_Helper();
			$db_helper		= new \WP_DGV_Db_Helper();
			//Get thumbnails
			if ( $vimeo_helper->is_connected) {
				$response	= $vimeo_helper->api->request("/videos/$vimeo_id/pictures?sizes=48x64", [], 'GET');
				$url		= $response['body']['data'][0]['base_link'].'.webp';
				if($url) $icon= $url;
			}
		}

	}
	
	return $icon;
}, 10, 9 );

/* add_filter('plupload_default_params', function($param){
	$param['action'] = 'upload-vimeo';
	return $param;
});

add_filter('plupload_default_settings', function($param){
	//$param['action'] = 'upload-vimeo';
	$param['url']=admin_url( 'admin-ajax.php', 'relative'  );
	return $param;
}); */


add_action('wp_ajax_upload-vimeo', function(){
	echo 'sdfds';
}); 

add_action('wp_ajax_prepare-vimeo-upload', function(){
	$vimeo_helper	= new \WP_DGV_Api_Helper();
	if ( $vimeo_helper->is_connected) {
		$params=[
			"upload" => [
				"approach" => "tus",
				"size" => $_POST['file_size']
			]
		];

		$response = $vimeo_helper->api->request('/me/videos?fields=uri,upload', $params, 'POST');

		wp_die($response['body']['upload']['upload_link']);
	}
	wp_die('no internet', 500);
}); 