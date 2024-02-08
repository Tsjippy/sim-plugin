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

	if(empty(get_post_meta($post->ID, 'send_signal', true))){
		return;
	}

	$signalMessageType	= get_post_meta($post->ID, 'signal_message_type', true);
	$signalUrl			= get_post_meta($post->ID, 'signal_url', true);
	$signalExtraMessage	= get_post_meta($post->ID, 'signal_extra_message', true);

	delete_post_meta($post->ID, 'send_signal');
	delete_post_meta($post->ID, 'signal_message_type');
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
		$message 	= "'{$post->post_title}' just got updated\n\n$excerpt";
	}else{
		$author		= get_userdata($post->post_author)->display_name;
		$message	= "'{$post->post_title}' just got published by $author\n\n$excerpt";
	}

	if(!empty($signalExtraMessage)){
		$message .=	"\n\n$signalExtraMessage";
	}

	$recipients		= SIM\getModuleOption(MODULE_SLUG, 'groups');

	SIM\printArray($recipients);

	foreach($recipients as $recipient){
		sendSignalMessage($message, $recipient, $post->ID);
	}
}

function asyncSignalMessageSend($message, $recipient, $postId=""){
	wp_schedule_single_event(time(), 'schedule_signal_message_action', [$message, $recipient, $postId]);
}

function sendSignalMessage($message, $recipient, $postId=""){

	// do not send on localhost
	if($_SERVER['HTTP_HOST'] == 'localhost'){
		return;
	}

	//remove https from site urldecode
	$urlWithoutHttps	= str_replace('https://', '', SITEURL);
	$message			= str_replace(SITEURL, $urlWithoutHttps, $message);

	$images = [];
	if(is_array($postId)){
		// Post id is a file pathe
		$images	= $postId;
	}elseif(is_numeric($postId) && has_post_thumbnail($postId)){
		$images = [get_attached_file(get_post_thumbnail_id($postId))];
	}

	if(SIM\getModuleOption(MODULE_SLUG, 'local')){
		$result	= sendSignalFromLocal($message, $recipient, $images);

		if(strpos($result, 'error') !== false){
			return $result;
		}elseif(!wp_doing_cron()){
			ob_start();
			?>
			<div class='success'>
				Message send succesfully
			</div>
			<?php

			return ob_get_clean();
		}
	}else{
		return sendSignalFromExternal($message, $recipient, $images);
	}
}

function sendSignalFromLocal($message, $recipient, $images){
	$phonenumber		= $recipient;

	//Check if recipient is an existing userid
	if(is_numeric($recipient) && get_userdata($recipient)){

		$phonenumber = get_user_meta( $recipient, 'signal_number', true );
	}

	if(empty($phonenumber)){
		//SIM\printArray("No Phonennumer $phonenumber", false, true);
		return false;
	}

	if(strlen($phonenumber) < 10){
		//SIM\printArray("Invalid Phonennumer $phonenumber", false, true);
		return false;
	}

	if(strpos(php_uname(), 'Linux') !== false){
		$signal = new SignalBus();
		if(strpos($phonenumber, ',') !== false){
			$signal->sendGroupMessage($message, $phonenumber, $images);
			return;
		}
	}else{
		$signal = new Signal();
	}

	$result	= $signal->send($phonenumber, $message, $images);

	if(strpos($result, 'Unregistered user') !== false){
		//user not registered
		delete_user_meta( $recipient, 'signal_number');
	}

	if(wp_doing_cron()){
		return '';
	}

	return $result;
}

function sendSignalFromExternal($message, $phonenumber, $image){
	if(!empty($image)){
		if(is_array($image)){
			$image	= $image[0];
		}
		$image	= base64_encode(file_get_contents($image));
	}
	
	$notifications = get_option('signal_bot_messages');

	//Notifications should be an array of recipients
	if(!is_array($notifications)){
		$notifications = [];
	}

	//The phonenumber should be an array of messages
	if(!isset($notifications[$phonenumber]) || !is_array($notifications[$phonenumber])){
		$notifications[$phonenumber]	= [];
	}

	$notifications[$phonenumber][] = [
		$message,
		$image
	];
	
	update_option('signal_bot_messages', $notifications);

	if(wp_doing_cron()){
		return '';
	}

	ob_start();
	?>
	<div class='success'>
		Message send succesfully
	</div>
	<?php

	return ob_get_clean();
}