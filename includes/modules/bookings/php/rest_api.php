<?php
namespace SIM\BOOKINGS;
use SIM;

add_action( 'rest_api_init', function () {
	//Route for notification messages
	register_rest_route( 
		RESTAPIPREFIX.'/bookings', 
		'/get_next_month', 
		array(
			'methods' 				=> 'POST',
			'callback' 				=> function(){
				$bookings	= new Bookings();

				$subject	= sanitize_text_field($_POST['subject']);

				$date		= strtotime($_POST['year'].'-'.$_POST['month'].'-01');
				return [
					'month'		=> $bookings->monthCalendar($subject, $date),
					'navigator'	=> $bookings->getNavigator($date)
				];
			},
			'permission_callback' 	=> '__return_true',
			'args'					=> array(
				'month'	=> array(
					'required'	=> true,
					'validate_callback' => 'is_numeric'
				),
				'year'		=> array(
					'required'	=> true,
					'validate_callback' => 'is_numeric'
				),
				'subject'		=> array(
					'required'	=> true
				)
			)
		)
	);
} );

//Function to return the first name of a user with a certain phone number
function find_firstname(\WP_REST_Request $request ) {	
	if (is_user_logged_in() && isset($request['phone'])){
		//Change the user to the adminaccount otherwise get_users will not work
		wp_set_current_user(1);
	
		$name = "not found";
		$users = get_users(array(
			'meta_key'     => 'phonenumbers',
		));

		foreach($users as $user){
			$phonenumbers = get_user_meta($user->ID, 'phonenumbers', true);
			if(in_array($request['phone'], $phonenumbers)){
				$name = $user->first_name;
			}
		}
		
		return $name;
	}
}