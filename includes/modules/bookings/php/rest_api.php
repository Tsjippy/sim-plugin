<?php
namespace SIM\BOOKINGS;
use SIM;

add_action( 'rest_api_init', function () {
	// Next month
	register_rest_route(
		RESTAPIPREFIX.'/bookings',
		'/get_next_month',
		array(
			'methods' 				=> 'POST',
			'callback' 				=> function(){
				$bookings	= new Bookings();

				$bookings->forms->shortcodeId	= $_POST['shortcodeid'];

				$bookings->forms->loadShortcodeData();

				$bookings->forms->getForm($bookings->forms->shortcodeData->form_id);

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

	// Approve pending booking
	register_rest_route(
		RESTAPIPREFIX.'/bookings',
		'/approve',
		array(
			'methods' 				=> 'POST',
			'callback' 				=> function(){
				$bookings	= new Bookings();

				return $bookings->updateBooking($_POST['id'], ['pending' => 0]);
			},
			'permission_callback' 	=> '__return_true',
			'args'					=> array(
				'id'	=> array(
					'required'	=> true,
					'validate_callback' => 'is_numeric'
				)
			)
		)
	);

	// Delete a booking
	register_rest_route(
		RESTAPIPREFIX.'/bookings',
		'/remove',
		array(
			'methods' 				=> 'POST',
			'callback' 				=> function(){
				$bookings	= new Bookings();

				$bookings->removeBooking($_POST['id']);

				return 'Booking removed succesfully';
			},
			'permission_callback' 	=> '__return_true',
			'args'					=> array(
				'id'	=> array(
					'required'	=> true,
					'validate_callback' => 'is_numeric'
				)
			)
		)
	);
} );

