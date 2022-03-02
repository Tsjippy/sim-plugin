<?php
namespace SIM;

add_action( 'rest_api_init', function () {
	//Route for prayerrequest of today
	register_rest_route( 'sim/v1', '/prayermessage', array(
		'methods' => 'GET',
		'callback' => 'SIM\bot_prayer',
		'permission_callback' => '__return_true',
		)
	);
	
	//Route for notification messages
	register_rest_route( 'sim/v1', '/notifications', array(
		'methods' => 'GET',
		'callback' => 'SIM\bot_messages',
		'permission_callback' => '__return_true',
		)
	);
	
	//Route for first names
	register_rest_route( 'sim/v1', '/firstname', array(
		'methods' => 'GET',
		'callback' => 'SIM\find_firstname',
		'permission_callback' => '__return_true',
		)
	);

	//Route for prayerrequest of today
	register_rest_route( 'simnigeria/v1', '/prayermessage', array(
		'methods' => 'GET',
		'callback' => 'SIM\bot_prayer',
		'permission_callback' => '__return_true',
		)
	);
	
	//Route for notification messages
	register_rest_route( 'simnigeria/v1', '/notifications', array(
		'methods' => 'GET',
		'callback' => 'SIM\bot_messages',
		'permission_callback' => '__return_true',
		)
	);
	
	//Route for first names
	register_rest_route( 'simnigeria/v1', '/firstname', array(
		'methods' => 'GET',
		'callback' => 'SIM\find_firstname',
		'permission_callback' => '__return_true',
		)
	);
} );

function bot_prayer() {
	if (is_user_logged_in()) {
		$message = "The prayer request of today is:\n";

		$message .= prayer_request(true);
		
		$params		= apply_filters('sim_after_bot_payer', ['message'=>$message, 'urls'=>'']);

		$message = $params['message']."\n\n".$params['urls'];
		
		return $message;
	}else{
		return "You have no permission to see this";
	}
}

function bot_messages( $delete = true) {
	if (is_user_logged_in()) {
		$notifications = get_option('signal_bot_messages');
		if($delete == true){
			delete_option('signal_bot_messages');
		}
		return $notifications;
	}
}

//Function to return the first name of a user with a certain phone number
function find_firstname(\WP_REST_Request $request ) {
	if (is_user_logged_in() and isset($request['phone'])){
		//Change the user to the adminaccount otherwise get_users will not work
		wp_set_current_user(1);
	
		$name = "not found";
		$users = get_users(array(
			'meta_key'     => 'phonenumbers',
		));

		foreach($users as $user){
			$phonenumbers = get_user_meta($user->ID,'phonenumbers',true);
			if(in_array($request['phone'],$phonenumbers)){
				$name = $user->first_name;
			}
		}
		
		return $name;
	}
}

//Post is published from backend
add_action(  'transition_post_status',  'SIM\check_if_signal_must_send', 10, 3 );
function check_if_signal_must_send( $new_status, $old_status, $post ) {
	// Check if signal nonce is set.
	if ($new_status == 'publish' and isset( $_POST['signal_message_meta_box_nonce'] ) ) {
		//Get the nonce from the post array
		$nonce = $_POST['signal_message_meta_box_nonce'];
		// Verify that the nonce is valid and checkbox to send is checked.
		if (wp_verify_nonce( $nonce, 'signal_message_meta_box') and isset($_POST['signal_message'])) {
			send_post_notification($post);
		}
	}
}

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
	$url_without_https = str_replace('https://','',get_site_url());
	
	$message = str_replace(get_site_url(),$url_without_https,$message);
	
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

//Add Signal messages overview shortcode
add_shortcode('signal_messages',function(){
	$signal_messages = get_option('signal_bot_messages');
	
	$html = '';
	
	//Perform remove action
	if(isset($_POST['recipient_number']) and isset($_POST['key'])){
		$html .= '<div class="success">Succesfully removed the message</div>';
	
		unset($signal_messages[$_POST['recipient_number']][$_POST['key']]);
		
		if(count($signal_messages[$_POST['recipient_number']]) == 0) unset($signal_messages[$_POST['recipient_number']]);
		
		update_option('signal_bot_messages',$signal_messages);
	}
	
	if(is_array($signal_messages) and count($signal_messages) >0){
		foreach($signal_messages as $recipient_number=>$recipient){
			$html .= "<strong>Messages to $recipient_number</strong><br>";
			foreach($recipient as $key=>$signal_message){
				$html .= 'Message '.($key+1).":<br>";
				$html .= $signal_message[0].'<br>';
				$html .= '<form action="" method="post">
					<input type="hidden" id="recipient_number" name="recipient_number" value="'.$recipient_number.'">
					<input type="hidden" id="key" name="key" value="'.$key.'">
					<button class="button remove signal_message sim" type="submit" style="margin-top:10px;">Remove this message</button>
				</form>';
			}
		}
	}else{
		$html = "No Signal messages found";
	}
	return $html;
});

//Function to add a checkbox for signal messages to a post
add_action( 'add_meta_boxes',  function() {
	add_meta_box( 'send-signal-message', 'Signal message', 'SIM\send_signal_message_meta_box', ['page','post','event'], 'side', 'high' );
	add_meta_box( 'send-signal-message-bottom', 'Signal message', 'SIM\send_signal_message_meta_box', ['page','post','event'], 'normal', 'high' );
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