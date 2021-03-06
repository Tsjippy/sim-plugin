<?php
namespace SIM\SIGNAL;
use SIM;

add_action( 'rest_api_init', function () {	
	//Route for notification messages
	register_rest_route( 'sim/v1', '/notifications', array(
		'methods' => 'GET',
		'callback' => __NAMESPACE__.'\botMessages',
		'permission_callback' => '__return_true',
		)
	);
	
	//Route for first names
	register_rest_route( 'sim/v1', '/firstname', array(
		'methods'				=> 'GET',
		'callback'				=> __NAMESPACE__.'\findFirstname',
		'permission_callback' 	=> '__return_true',
		)
	);
	
	//Route for notification messages
	register_rest_route( 'simnigeria/v1', '/notifications', array(
		'methods' 				=> 'GET',
		'callback' 				=> __NAMESPACE__.'\botMessages',
		'permission_callback' 	=> '__return_true',
		)
	);
	
	//Route for first names
	register_rest_route( 'simnigeria/v1', '/firstname', array(
		'methods' => 'GET',
		'callback' => __NAMESPACE__.'\findFirstname',
		'permission_callback' => '__return_true',
		)
	);

	// Update quota documents
	register_rest_route( 
		'sim/v1/signal', 
		'/save_preferences', 
		array(
			'methods' 				=> \WP_REST_Server::EDITABLE,
			'callback' 				=> __NAMESPACE__.'\savePreferences',
			'permission_callback' 	=> '__return_true',
			'args'					=> array(
				'userid'		=> array(
					'required'	=> true,
                    'validate_callback' => 'is_numeric'
                )
			)
		)
	);
} );

function botMessages( $delete = true) {
	if (is_user_logged_in()) {
		$notifications = get_option('signal_bot_messages');
		if($delete == true){
			delete_option('signal_bot_messages');
		}
		return $notifications;
	}
}

//Function to return the first name of a user with a certain phone number
function findFirstname(\WP_REST_Request $request ) {
	if (is_user_logged_in() && isset($request['phone'])){
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

function savePreferences(){
	$userId			= $_POST['userid'];
	$currentUser	= wp_get_current_user();

	// If updating for someone else, check if we have the right to do so
	if($userId	!= $currentUser->ID && !array_intersect(['usermanagement'], $currentUser->roles)){
		return new \WP_Error('signal', 'You have no permission to this!');
	}

	$signalPreferences	= $_POST;
	unset($signalPreferences['userid']);
	unset($signalPreferences['_wpnonce']);

	do_action('sim_signal_before_pref_save', $userId, $signalPreferences);

	update_user_meta($userId, 'signal_preferences', $signalPreferences);

	if($userId	!= $currentUser->ID){
		$name	= get_userdata($userId)->display_name;
		return "Succesfully saved Signal preferences for $name";
	}
	return 'Succesfully saved your Signal preferences';
}