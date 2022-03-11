<?php
namespace SIM\SIGNAL;
use SIM;

add_action( 'rest_api_init', function () {	
	//Route for notification messages
	register_rest_route( 'sim/v1', '/notifications', array(
		'methods' => 'GET',
		'callback' => __NAMESPACE__.'\bot_messages',
		'permission_callback' => '__return_true',
		)
	);
	
	//Route for first names
	register_rest_route( 'sim/v1', '/firstname', array(
		'methods'				=> 'GET',
		'callback'				=> __NAMESPACE__.'\find_firstname',
		'permission_callback' 	=> '__return_true',
		)
	);
	
	//Route for notification messages
	register_rest_route( 'simnigeria/v1', '/notifications', array(
		'methods' 				=> 'GET',
		'callback' 				=> __NAMESPACE__.'\bot_messages',
		'permission_callback' 	=> '__return_true',
		)
	);
	
	//Route for first names
	register_rest_route( 'simnigeria/v1', '/firstname', array(
		'methods' => 'GET',
		'callback' => __NAMESPACE__.'\find_firstname',
		'permission_callback' => '__return_true',
		)
	);
} );

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