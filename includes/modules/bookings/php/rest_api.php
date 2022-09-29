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

				$bookings->forms->shortcodeId	= $_POST['shortcodeid'];

				$subject	= sanitize_text_field($_POST['subject']);

				$date		= strtotime($_POST['year'].'-'.$_POST['month'].'-01');
				return [
					'month'		=> $bookings->monthCalendar($subject, $date),
					'navigator'	=> $bookings->getNavigator($date, 1),
					'details'	=> $bookings->detailHtml()
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

