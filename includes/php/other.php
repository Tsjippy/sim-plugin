<?php
namespace SIM;

//Change the timeout on post locks
add_filter( 'wp_check_post_lock_window', function(){ return 70;});

//Change the extension of all jpg like files to jpe so that they are not directly available for non-logged in users
add_filter('wp_handle_upload_prefilter', function ($file) {
    $info 	= pathinfo($file['name']);
    $ext  	= empty($info['extension']) ? '' : '.' . $info['extension'];
	$name 	= basename($file['name'], $ext);
	$ext 	= strtolower($ext);
	
	//Change the extension to jpe
	if($ext == ".jpg" || $ext == ".jpeg" || $ext == ".jfif" || $ext == ".exif"){
		$ext = ".jpe";
	}
	
	$file['name'] = $name . $ext;

	return $file;
}, 1, 1);

// Disable auto-update email notifications for plugins.
add_filter( 'auto_plugin_update_send_email', '__return_false' );
// Disable auto-update email notifications for themes.
add_filter( 'auto_theme_update_send_email', '__return_false' );

//Hide adminbar
add_action('after_setup_theme', function () {
	if (!current_user_can('administrator') && !is_admin()) {
		show_admin_bar(false);
	}
});

//convert jpeg to webp doesnt seem to work
add_filter( 'image_editor_output_format', function( $formats ) {
	$formats['image/jpg'] = 'image/webp';
	$formats['image/jpe'] = 'image/webp';
	return $formats;
});

//First acions for staging sites
if(get_option("wpstg_is_staging_site") == "true"){
	require_once(ABSPATH.'wp-admin/includes/user.php');
	
	add_action( 'init', function() {
		if(strpos($_SERVER['REQUEST_URI'], 'options-permalink.php') !== false && get_option("first_run") == ""){
			flush_rewrite_rules();

			//Indicate that the first run has been done
			update_option("first_run","first_run");
			//Get all users
			$users = get_users();
			//Only keep admins and editors
			$allowedRoles = array('medicalinfo','administrator','editor');
 			foreach($users as $user){
				//If this user is not an admin or editor
				if( !array_intersect($allowedRoles, $user->roles ) ) {
					error_log("Deleting user with id {$user->ID} as this is an staging site");
					//Delete user and assign its contents to the admin user
					wp_delete_user($user->ID,1);
				}
			}
			global $wp_rewrite;
			//Set the permalinks
			$wp_rewrite->set_permalink_structure( '/%category%/%postname%/' );
			$wp_rewrite->flush_rules();
		}
	} );
}

//Keep line breaks in excerpts
remove_filter('get_the_excerpt', 'wp_trim_excerpt');
add_filter('get_the_excerpt', __NAMESPACE__.'\customExcerpt', 10, 2);
add_filter('the_excerpt', __NAMESPACE__.'\customExcerpt', 10, 2);
function customExcerpt($excerpt, $post=null) {
	$rawExcerpt = $excerpt;
	
	if ( empty($excerpt)) {
		//Retrieve the post content.
		if(!empty($post)){
			$excerpt = $post->post_content;
		}
		
		//Delete all shortcode tags from the content.
		$excerpt 			= strip_shortcodes( $excerpt );
		
		$excerpt 			= str_replace(["]]>", "<p>", "</p>"], ["]]&gt;", "<br>", ""] , $excerpt);
		$allowedTags 		= '<br>,<strong>';
		$excerpt 			= strip_tags($excerpt, $allowedTags);

		while(substr($excerpt, 0, 4) == '<br>'){
			$excerpt	= trim(substr($excerpt, 4));
		}
		 
		$excerptWordCount 	= 45;
		$excerptLength 		= apply_filters('excerpt_length', $excerptWordCount);
		 
		$excerptEnd			= '[...]';
		$excerptMore 		= apply_filters('excerpt_more', ' ' . $excerptEnd);
		 
		$words = preg_split("/[\n\r\t ]+/", $excerpt, $excerptLength + 1, PREG_SPLIT_NO_EMPTY);
		if ( count($words) > $excerptLength ) {
			array_pop($words);
			$excerpt = implode(' ', $words);
			$excerpt = "<div class='excerpt'>$excerpt</div>$excerptMore";
		} else {
			$excerpt = implode(' ', $words);
		}
	}

	return apply_filters('wp_trim_excerpt', $excerpt, $rawExcerpt);
}

// Turn off heartbeat
add_action( 'init', function(){wp_deregister_script('heartbeat');}, 1 );

//Remove the password protect of a page for logged in users
add_filter( 'post_password_required',
	function( $returned, $post ){
		// Override it for logged in users:
		if( $returned && is_user_logged_in() )
			$returned = false;

		return $returned;
	}
	, 10, 2
);

// Make sure only the rest api response is echood and nothing else
add_filter( 'rest_request_after_callbacks', function($response){
	clearOutput();
	return $response;
});