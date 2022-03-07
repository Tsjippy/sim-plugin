<?php
namespace SIM\FRONTPAGE;
use SIM;

//Add a shortcode for the displayname
add_shortcode( 'displayname', function ( $atts ) {
	if (is_user_logged_in()){
		$current_user = wp_get_current_user();
		return $current_user->first_name;
	}else{
		return "visitor";	
	}
});

//Shortcode to return the amount of loggins in words
add_shortcode("login_count",function ($atts){
	$UserID = get_current_user_id();
	$current_loggin_count = get_user_meta( $UserID, 'login_count', true );
	//Get the word from the array
	if (is_numeric($current_loggin_count)){
		return SIM\number_to_words($current_loggin_count);
	//key not set, assume its the first time
	}else{
		return "your first";
	}
});

//Shortcode for the welcome message on the homepage
add_shortcode("welcome",function ($atts){
	if (is_user_logged_in()){
		global $WelcomeMessagePageID;
		$UserID = get_current_user_id();
		//Check welcome message needs to be shown
		$show_welcome = get_user_meta( $UserID, 'welcomemessage', true );
		if ($show_welcome == ""){
			$welcome_post = get_post($WelcomeMessagePageID); 
			if($welcome_post != null){
				//Load js
				wp_enqueue_script('sim_message_script');
				
				//Html
				$html = '<div id="welcome-message">';
				$html .= '<h4>'.$welcome_post->post_title.'</h4>';
				$html .= apply_filters('the_content',$welcome_post->post_content);
				$html .= '<button type="button" class="button" id="welcome-message-button">Do not show again</button></div>';
				return $html;
			}
		}
	}
});