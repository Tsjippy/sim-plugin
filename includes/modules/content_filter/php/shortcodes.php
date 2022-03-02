<?php
namespace SIM\CONTENTFILTER;
use SIM;

// Make partial page contents filterable
add_shortcode( 'content_filter', function ( $atts = array(), $content = null ) {
	$a = shortcode_atts( array(
        'inversed' => false,
		'roles' => "All",
    ), $atts );
	$inversed 		= $a['inversed'];
	$allowed_roles 	= explode(',',$a['roles']);
	$return = false;
	
    //Get the current user
	$user = wp_get_current_user();
	
	//User is logged in
	if(is_user_logged_in()){
		if( in_array('All',$allowed_roles) or array_intersect($allowed_roles, $user->roles)) { 
			// display content
			$return = true;
		}
	}
    
	//If inversed
	if($inversed){
		//Swap the outcome
		$return = !$return;
	}
	
	//If return is true
	if($return == true){
		//return the shortcode content
		return do_shortcode($content);
	}
});