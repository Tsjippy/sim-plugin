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
add_shortcode("login_count", function ($atts){
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

// Wrapper function for the home page for logged in users
add_shortcode('logged_home_page', function($atts){
	$html	= apply_filters('sim_loggedin_homepage', '');
	return $html;
});