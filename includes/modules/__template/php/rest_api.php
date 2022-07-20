<?php
namespace SIM\ADMIN;
use SIM;

add_action( 'rest_api_init', function () {
	//Route for notification messages
	register_rest_route( 
		'sim/v1/MODULENAME', 
		'/add_category', 
		array(
			'methods' 				=> 'POST',
			'callback' 				=> __NAMESPACE__.'\addCategory',
			'permission_callback' 	=> '__return_true',
			'args'					=> array(
				'cat_name'		=> array('required'	=> true),
				'cat_parent'	=> array(
					'required'	=> true,
					'validate_callback' => 'is_numeric'
				),
				'post_type'		=> array(
					'required'	=> true,
					'validate_callback' => function($param) {
						return in_array($param, get_post_types());
					}
				),
			)
		)
	);
} );

//Function to return the first name of a user with a certain phone number
function find_firstname(\WP_REST_Request $request ) {
	$mailId		= $request->get_param('mailid');
	
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