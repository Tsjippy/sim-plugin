<?php
namespace SIM\FRONTPAGE;
use SIM;

//Add a shortcode for the displayname
add_shortcode( 'displayname', function () {
	if (is_user_logged_in()){
		$currentUser = wp_get_current_user();
		return $currentUser->first_name;
	}else{
		return "visitor";	
	}
});

//Shortcode to return the amount of loggins in words
add_shortcode("login_count", function (){
	$UserID 			= get_current_user_id();
	$currentLogginCount = get_user_meta( $UserID, 'login_count', true );
	//Get the word from the array
	if (is_numeric($currentLogginCount)){
		return SIM\numberToWords($currentLogginCount);
	//key not set, assume its the first time
	}else{
		return "your first";
	}
});

// Wrapper function for the home page for logged in users
add_shortcode('logged_home_page', function(){
	return apply_filters('sim_loggedin_homepage', '');
});