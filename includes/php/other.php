<?php
namespace SIM;

//If no featured image on post is set, set one
add_action( 'save_post', function ( $post_id, $post, $update ) {
	global $Maps;
	
	//This hook fires multiple times, we should only check the last time
	if($post->post_date != $post->post_modified and $post->post_status == "publish"){
		global $MissionariesPageID;
		$parents = get_post_ancestors( $post_id );
		
		//Post has no featured image and is about to get published
		if (!has_post_thumbnail($post_id)) {
			set_default_picture($post_id);
		//Post is an missionary page and has an featured image and is a family page, use posts image as marker icon image
		}elseif(in_array($MissionariesPageID,$parents) and has_post_thumbnail($post_id) and strpos($post->post_title, 'amily') !== false){
			$author_id = $post->post_author;
			//Personal marker id
			$marker_id = get_user_meta($author_id,"marker_id",true);
			if(is_numeric($marker_id)){
				//Update the family marker
				$Maps->create_icon($marker_id, get_userdata($author_id)->last_name." family", get_the_post_thumbnail_url($post_id), 1);
			}
		}
	}
}, 10,3 );

//Only show read more on home and news page
add_filter( 'excerpt_more', function ( $more ) {
		return '<a class="moretag" href="' . get_the_permalink() . '">Read More »</a>';
}, 999  );

//Change the timeout on post locks
add_filter( 'wp_check_post_lock_window', function(){ return 70;});

//Change the extension of all jpg like files to jpe so that they are not directly available for non-logged in users
add_filter('wp_handle_upload_prefilter', function ($file) {
	global $post;
    $info = pathinfo($file['name']);
    $ext  = empty($info['extension']) ? '' : '.' . $info['extension'];
	$name = basename($file['name'], $ext);
	$ext = strtolower($ext);
	//Change the extension to jpe
	if($ext == ".jpg" or $ext == ".jpeg" or $ext == ".jfif" or $ext == ".exif"){
		$ext = ".jpe";
	}
	
	$file['name'] = $name . $ext;

	return $file;
}, 1, 1);

// Disable auto-update email notifications for plugins.
add_filter( 'auto_plugin_update_send_email', '__return_false' );
 
// Disable auto-update email notifications for themes.
add_filter( 'auto_theme_update_send_email', '__return_false' );

//Modify default registration message
add_filter( 'wp_login_errors', function ($errors, $redirect_to){
	if ( in_array( 'registered', $errors->get_error_codes(), true ) ) {
		$message = 'Registration complete. You will receive an email once your registration was confirmed by an administrator.<br>Please check your spamfolder if you do not receive an e-mail within 2 days.';
		$errors->remove( 'registered' );
		$errors->add( 'registered', $message, 'message' );
	}
	return $errors;
}, 99, 2 );

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
		if(strpos($_SERVER['REQUEST_URI'], 'options-permalink.php') !== false and get_option("first_run") == ""){
			//Indicate that the first run has been done
			update_option("first_run","first_run");
			//Get all users
			$users = get_users();
			//Only keep admins and editors
			$allowed_roles = array('medicalinfo','administrator','contentmanager');
 			foreach($users as $user){
				//If this user is not an admin or editor
				if( !array_intersect($allowed_roles, $user->roles ) ) { 
					error_log("Deleting user with id ".$user->ID." as this is an staging site");
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
add_filter('get_the_excerpt', 'SIM\custom_excerpt');
add_filter('the_excerpt', 'SIM\custom_excerpt');
function custom_excerpt($text) {
	$raw_excerpt = $text;
	
	if ( '' == $text ) {
		//Retrieve the post content. 
		$text = get_the_content('');
		
		//Delete all shortcode tags from the content. 
		$text = strip_shortcodes( $text );
		
		$text = str_replace(']]>', ']]&gt;', $text);
		$text = str_replace("<p>","<br>", $text);
		$allowed_tags = '<br>,<strong>'; 
		$text = strip_tags($text, $allowed_tags);
		 
		$excerpt_word_count = 45; 
		$excerpt_length = apply_filters('excerpt_length', $excerpt_word_count); 
		 
		$excerpt_end = '[...]'; 
		$excerpt_more = apply_filters('excerpt_more', ' ' . $excerpt_end);
		 
		$words = preg_split("/[\n\r\t ]+/", $text, $excerpt_length + 1, PREG_SPLIT_NO_EMPTY);
		if ( count($words) > $excerpt_length ) {
			array_pop($words);
			$text = implode(' ', $words);
			$text = $text . $excerpt_more;
		} else {
			$text = implode(' ', $words);
		}
	}

	return apply_filters('wp_trim_excerpt', $text, $raw_excerpt);
}

set_error_handler('SIM\handle_error');
function handle_error( $errno, $errstr, $errfile, $errline ) {

    if( $errno === E_USER_NOTICE ) {

        $message = 'You have an error notice: "%s" in file "%s" at line: "%s".' ;
        $message = sprintf($message, $errstr, $errfile, $errline);

        print_array($message);
        print_array(wpse_288408_generate_stack_trace());
    }
}
// Function from php.net http://php.net/manual/en/function.debug-backtrace.php#112238
function wpse_288408_generate_stack_trace() {

    $e = new \Exception();

    $trace = explode( "\n" , $e->getTraceAsString() );

    // reverse array to make steps line up chronologically

    $trace = array_reverse($trace);

    array_shift($trace); // remove {main}
    array_pop($trace); // remove call to this method

    $length = count($trace);
    $result = array();

    for ($i = 0; $i < $length; $i++) {
        $result[] = ($i + 1)  . ')' . substr($trace[$i], strpos($trace[$i], ' ')); // replace '#someNum' with '$i)', set the right ordering
    }

    $result = implode("\n", $result);
    $result = "\n" . $result . "\n";

    return $result;
}

//add message to e-mail
add_filter('wp_mail',function($args){
	//force html e-mail
	if(!is_array($args['headers'])) $args['headers'] = [];
	if(!in_array("Content-Type: text/html; charset=UTF-8", $args['headers'])){
		$args['headers'][]	= "Content-Type: text/html; charset=UTF-8";
	}
	
	if(strpos($args['message'], 'is an automated') === false){
		$args['message']	.= "<br><br>";
		$url				 = get_site_url();
		$clean_url			 =  str_replace('https://','',$url);
		$args['message']	.= "<span style='font-size:10px'>This is an automated e-mail originating from <a href='$url'>$clean_url</a></span>";
	}

	return $args;
}, 10,1);

add_action( 'init', function(){wp_deregister_script('heartbeat');}, 1 );