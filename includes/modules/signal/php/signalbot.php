<?php
namespace SIM\SIGNAL;
use SIM;

//Post is published from backend
add_action(  'transition_post_status',  function( $new_status, $old_status, $post ) {
	// Check if signal nonce is set.
	if ($new_status == 'publish' and isset( $_POST['signal_message_meta_box_nonce'] ) ) {
		//Get the nonce from the post array
		$nonce = $_POST['signal_message_meta_box_nonce'];
		// Verify that the nonce is valid and checkbox to send is checked.
		if (wp_verify_nonce( $nonce, 'signal_message_meta_box') and isset($_POST['signal_message'])) {
			send_post_notification($post);
		}
	}
}, 10, 3 );

function send_post_notification($post){
	if(is_numeric($post)){
		$post = get_post($post);
	}
	
	if($_POST['signalmessagetype'] == 'all'){
		$excerpt	= $post->post_content;
	}else{
		$excerpt	= wp_trim_words(do_shortcode($post->post_content), 20);
		//Only add read more if the excerpt is not the whole content
		if($excerpt != strip_tags($post->post_content)){
			$excerpt .=	"...\n\nRead more on:\n".get_permalink($post);
		}
	}
	$excerpt = html_entity_decode($excerpt);
	
	$excerpt = strip_tags(str_replace('<br>',"\n",$excerpt));

	$excerpt = apply_filters('sim_signal_post_notification_message', $excerpt, $post);

	if(!empty($_POST['pagetype']['everyone'])) $excerpt	.= "\n\nThis is a mandatory message, please read it straight away.";
	
	if($_POST['update'] == 'true'){
		$message = "'{$post->post_title}' just got updated\n\n$excerpt";
	}else{
		$message = "'{$post->post_title}' just got published\n\n$excerpt";
	}
	send_signal_message(
		$message,
		"all",
		$post->ID
	);
}

function send_signal_message($message, $recipient, $post_id=""){
	//remove https from site urldecode
	$url_without_https = str_replace('https://', '', SITEURL);
	
	$message = str_replace(SITEURL,$url_without_https,$message);
	
	//Check if recipient is an existing userid
	if(is_numeric($recipient) and get_userdata($recipient)){
		$phonenumbers = get_user_meta( $recipient, 'phonenumbers', true );
		
		//If this user has more than 1 phone number add them all
		if(is_array($phonenumbers) and count($phonenumbers) == 1){
			//Store the first and only phonenumber as recipient
			$recipient = array_values($phonenumbers)[0];
		}elseif(is_array($phonenumbers) and count($phonenumbers) > 1){
			foreach($phonenumbers as $phonenumber){
				send_signal_message($message,$phonenumber,$post_id);
			}
			return;
		}else{
			return;
		}
	}
	
	$notifications = get_option('signal_bot_messages');
	//Notifications should be an array of recipients
	if(!is_array($notifications)) $notifications = [];

	//The recipient should be an array of messages
	if(!isset($notifications[$recipient]) or !is_array($notifications[$recipient])) $notifications[$recipient]=[];
	
	if(is_numeric($post_id) and has_post_thumbnail($post_id)){
		$image = base64_encode(file_get_contents(get_attached_file(get_post_thumbnail_id($post_id))));
	}else{
		$image = "";
	}
	$notifications[$recipient][] = [
		$message,
		$image
	];
	update_option('signal_bot_messages',$notifications);
}

//Function to add a checkbox for signal messages to a post
add_action( 'add_meta_boxes',  function() {
	add_meta_box( 'send-signal-message', 'Signal message', __NAMESPACE__.'\send_signal_message_meta_box', ['page','post','event'], 'side', 'high' );
	add_meta_box( 'send-signal-message-bottom', 'Signal message', __NAMESPACE__.'\send_signal_message_meta_box', ['page','post','event'], 'normal', 'high' );
});

//Display the send signal meta box
function send_signal_message_meta_box() {
	global $post;

	// Add an nonce field so we can check for it later.
	wp_nonce_field( 'signal_message_meta_box', 'signal_message_meta_box_nonce' );
	
	//Check the box by default for posts
	if($post->post_type == 'post' and $post->post_status != 'publish'){
		$checked = "checked";
	}else{
		$checked = "";
	}
	
	//Output the checkbox
	echo "<input type='checkbox' id='signal_message' name='signal_message' value='sent' $checked>";
	echo '<label for="signal_message"> Send Signal message on publish</label><br>';
}