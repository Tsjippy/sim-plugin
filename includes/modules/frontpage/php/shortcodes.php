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
		$UserID = get_current_user_id();
		//Check welcome message needs to be shown
		if (empty(get_user_meta( $UserID, 'welcomemessage', true ))){
			$welcome_message = SIM\get_module_option('frontpage', 'welcome_message'); 
			if(!empty($welcome_message)){
				//Html
				$html = '<div id="welcome-message">';
					$html .= do_shortcode($welcome_message);
					$html .= '<button type="button" class="button" id="welcome-message-button">Do not show again</button>';
				$html .= '</div>';
				return $html;
			}
		}
	}
});