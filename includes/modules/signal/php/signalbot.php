<?php
namespace SIM\SIGNAL;
use SIM;

//Post is published from backend
add_action(  'transition_post_status',  function( $newStatus, $oldStatus, $post ) {
	// Check if signal nonce is set.
	if ($newStatus == 'publish'){
		if(isset( $_POST['signal_message_meta_box_nonce'] ) ) {
			//Get the nonce from the post array
			$nonce = $_POST['signal_message_meta_box_nonce'];
			// Verify that the nonce is valid and checkbox to send is checked.
			if (wp_verify_nonce( $nonce, 'signal_message_meta_box') && isset($_POST['signal_message'])) {
				sendPostNotification($post);
			}
		}elseif($oldStatus != 'publish' && $oldStatus != 'new'){
			sendPostNotification($post);
		}
	}
}, 10, 3 );

/**
 *
 * Sends a summary or full post to the Signal group when post is published
 *
 * @param    object|int $post       Wordpress post or post id
 *
**/
function sendPostNotification($post){
	if(is_numeric($post)){
		$post = get_post($post);
	}

	if(empty(get_post_meta($post->ID, 'signal', 'checked'))){
		return;
	}

    $signalMessageType	= get_post_meta($post->ID, 'signalmessagetype', true);
	$signalUrl			= get_post_meta($post->ID, 'signal_url', true);
	$signalExtraMessage	= get_post_meta($post->ID, 'signal_extra_message', true);

	delete_post_meta($post->ID, 'signal');
	delete_post_meta($post->ID, 'signalmessagetype');
	delete_post_meta($post->ID, 'signal_url');
	delete_post_meta($post->ID, 'signal_extra_message');
	
	if($signalMessageType == 'all'){
		$excerpt	= $post->post_content;

		if(!empty($signalUrl)){
			$excerpt .=	"...\n\nView it on the web:\n".get_permalink($post->ID);
		}
	}else{
		$excerpt	= wp_trim_words(do_shortcode($post->post_content), 20);
		
		//Only add read more if the excerpt is not the whole content
		if($excerpt != strip_tags($post->post_content)){
			$excerpt .=	"...\n\nRead more on:\n".get_permalink($post->ID);
		}elseif(!empty($signalUrl)){
			$excerpt .=	"...\n\nView it on the web:\n".get_permalink($post->ID);
		}
	}
	$excerpt = html_entity_decode($excerpt);
	
	$excerpt = strip_tags(str_replace('<br>',"\n",$excerpt));

	$excerpt = apply_filters('sim_signal_post_notification_message', $excerpt, $post);
	
	if($_POST['update'] == 'true'){
		$message = "'{$post->post_title}' just got updated\n\n$excerpt";
	}else{
		$message = "'{$post->post_title}' just got published\n\n$excerpt";
	}

	if(!empty($signalExtraMessage)){
		$message .=	"\n\n$signalExtraMessage";
	}

	$recipients		= SIM\getModuleOption(MODULE_SLUG, 'groups');

	foreach($recipients as $recipient){
		sendSignalMessage($message, $recipient, $post->ID);
	}
}

function sendSignalMessage($message, $recipient, $postId=""){
	//remove https from site urldecode
	$urlWithoutHttps	= str_replace('https://', '', SITEURL);
	$message			= str_replace(SITEURL, $urlWithoutHttps, $message);
	
	//Check if recipient is an existing userid
	if(is_numeric($recipient) && get_userdata($recipient)){
		$phonenumbers = get_user_meta( $recipient, 'phonenumbers', true );
		
		//If this user has more than 1 phone number add them all
		if(is_array($phonenumbers) && count($phonenumbers) == 1){
			//Store the first and only phonenumber as recipient
			$recipient = array_values($phonenumbers)[0];
		}elseif(is_array($phonenumbers) && count($phonenumbers) > 1){
			foreach($phonenumbers as $phonenumber){
				sendSignalMessage($message, $phonenumber, $postId);
			}
			return;
		}else{
			return;
		}
	}
	
	$notifications = get_option('signal_bot_messages');
	//Notifications should be an array of recipients
	if(!is_array($notifications)){
		$notifications = [];
	}

	//The recipient should be an array of messages
	if(!isset($notifications[$recipient]) || !is_array($notifications[$recipient])){
		$notifications[$recipient]	= [];
	}
	
	$image = "";
	if(is_numeric($postId) && has_post_thumbnail($postId)){
		$image = base64_encode(file_get_contents(get_attached_file(get_post_thumbnail_id($postId))));
	}

	$notifications[$recipient][] = [
		$message,
		$image
	];
	
	update_option('signal_bot_messages', $notifications);
}

//Function to add a checkbox for signal messages to a post
add_action( 'add_meta_boxes',  function() {
	add_meta_box( 'send-signal-message', 'Signal message', __NAMESPACE__.'\sendSignalMessageMetaBox', ['page','post','event'], 'side', 'high' );
	add_meta_box( 'send-signal-message-bottom', 'Signal message', __NAMESPACE__.'\sendSignalMessageMetaBox', ['page','post','event'], 'normal', 'high' );
});

//Display the send signal meta box
function sendSignalMessageMetaBox() {
	global $post;

	// Add an nonce field so we can check for it later.
	wp_nonce_field( 'signal_message_meta_box', 'signal_message_meta_box_nonce' );
	
	//Check the box by default for posts
	if($post->post_type == 'post' && $post->post_status != 'publish'){
		$checked = "checked";
	}else{
		$checked = "";
	}
	
	//Output the checkbox
	echo "<input type='checkbox' id='signal_message' name='signal_message' value='sent' $checked>";
	echo '<label for="signal_message"> Send Signal message on publish</label><br>';
}