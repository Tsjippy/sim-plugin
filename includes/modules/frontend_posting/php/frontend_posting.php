<?php
namespace SIM\FRONTEND_POSTING;
use SIM;

add_action('init', function(){
	$GLOBALS['FrontEndContent']	= new FrontEndContent();
});

function send_pending_post_warning($post, $update){
	global $Modules;
	global $WebmasterName;
	
	//Do not continue if already send
	if(get_post_meta($post->ID,'pending_notification_send',true) != '') return;
	
	//get all the content managers
	$users = get_users( array(
		'role'    => 'contentmanager',
	));
	
	if($update){
		$action_text = 'updated';
	}else{
		$action_text = 'created';
	}
	
	$type = $post->post_type;
	
	//send notification to all content managers
	$url = add_query_arg( ['post_id' => $post->ID], get_permalink( $Modules['frontend_posting']['publish_post_page'] ) );
	$author_name = get_userdata($post->post_author)->display_name;
	
	foreach($users as $user){
		//send signal message
		SIM\try_send_signal("$author_name just $action_text a $type. Please review it here:\n\n$url",$user->ID);
		
		//Send e-mail
		$message = "Hi ".$user->first_name."<br><br>";
		$message .= "$author_name just $action_text a $type. Please review it <a href='$url'>here</a><br><br>";
		$message .= 'Cheers,<br><br>'.$WebmasterName;
		$headers = ['Content-Type: text/html; charset=UTF-8'];

		wp_mail( $user->user_email, "Please review a $type", $message, $headers );
	}
	
	//Mark warning as send
	update_post_meta($post->ID,'pending_notification_send',true);
}

//Delete the indicator that the warning has been send
add_action(  'transition_post_status',  function ( $new_status, $old_status, $post ) {
	// Check if signal nonce is set.
	if ($new_status == 'publish' and $old_status == 'pending'){
		delete_post_meta($post->ID,'pending_notification_send');
	}
}, 10, 3 );

//Allow display attributes in post content
add_filter( 'safe_style_css', function( $styles ) {
    $styles[] = 'display';
    return $styles;
} );