<?php
namespace SIM\PRAYER;
use SIM;

add_action( 'rest_api_init', function () {
	//Route for prayerrequest of today
	register_rest_route( 'sim/v1', '/prayermessage', array(
		'methods' => 'GET',
		'callback' => 'SIM\SIGNAL\bot_prayer',
		'permission_callback' => '__return_true',
		)
	);

	//Route for prayerrequest of today
	register_rest_route( 'simnigeria/v1', '/prayermessage', array(
		'methods' => 'GET',
		'callback' => 'SIM\SIGNAL\bot_prayer',
		'permission_callback' => '__return_true',
		)
	);
} );

function bot_prayer() {
	if (is_user_logged_in()) {
		$message	 = "The prayer request of today is:\n";

		$message 	.= prayer_request(true);
		
		$params		 = apply_filters('sim_after_bot_payer', ['message'=>$message, 'urls'=>'']);

		$message	 = $params['message']."\n\n".$params['urls'];
		
		return $message;
	}else{
		return "You have no permission to see this";
	}
}