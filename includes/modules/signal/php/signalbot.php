<?php
namespace SIM\SIGNAL;
use SIM;

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

	if(empty(get_post_meta($post->ID, 'signal_message_type', true))){
		return;
	}

    $signalMessageType	= get_post_meta($post->ID, 'signal_message_type', true);
	$signalUrl			= get_post_meta($post->ID, 'signal_url', true);
	$signalExtraMessage	= get_post_meta($post->ID, 'signal_extra_message', true);

	delete_post_meta($post->ID, 'signal');
	delete_post_meta($post->ID, 'signal_message_type');
	delete_post_meta($post->ID, 'signal_url');
	delete_post_meta($post->ID, 'signal_extra_message');
	
	SIM\printArray($post, true);

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
		$message 	= "'{$post->post_title}' just got updated\n\n$excerpt";
	}else{
		$author		= get_userdata($post->post_author)->display_name;
		$message	= "'{$post->post_title}' just got published by $author\n\n$excerpt";
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
	// do not send on localhost
	if($_SERVER['HTTP_HOST'] == 'localhost'){
		return;
	}
	
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

	$image = "";
	if(is_numeric($postId) && has_post_thumbnail($postId)){
		$image = get_attached_file(get_post_thumbnail_id($postId));
	}

	if(SIM\getModuleOption(MODULE_SLUG, 'local')){
		if(strpos(php_uname(), 'Linux') !== false){
			$signal = new SignalBus();
		}else{
			$signal = new Signal();
		}
		
		$signal->send($recipient, $message, $image);

		return;
	}

	if(!empty($image)){
		$image	= base64_encode(file_get_contents($image));
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

	$notifications[$recipient][] = [
		$message,
		$image
	];
	
	update_option('signal_bot_messages', $notifications);
}