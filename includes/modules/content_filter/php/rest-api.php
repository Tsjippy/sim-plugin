<?php
namespace SIM\CONTENTFILTER;
use SIM;

//Secure the rest api
add_filter( 'rest_authentication_errors', function( $result ) {	
    // If a previous authentication check was applied, pass that result along without modification.
    if ( true === $result || is_wp_error( $result ) ) {
        return $result;
    }
    
    // No authentication has been performed yet return an error if user is not logged in, exception for wp-mail-smtp
    if ( 
		is_user_logged_in() or 
		is_allowed_rest_api_url()
	) {
		// Our custom authentication check should have no effect on logged-in requests
		return $result;
    }else{
		wp_die('You do not have permission for this.');
	}
});

function is_allowed_rest_api_url(){
	$urls	= [
		'wp-mail-smtp/v1'
	];

	$urls	= apply_filters('sim_allowed_rest_api_urls', $urls);

	foreach($urls as $url){
		if(strpos($_SERVER['REQUEST_URI'], $url) !== false){
			return true;
		}
	}

	return false;
}