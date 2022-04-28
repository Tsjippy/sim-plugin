<?php
namespace SIM\EVENTS;
use SIM;

add_action( 'rest_api_init', function () {
	// Month calendar
	register_rest_route( 
		'sim/v1/events', 
		'/get_month_html', 
		array(
			'methods' 				=> 'POST',
			'callback' 				=> function(){
				$events		= new Events();
				return $events->month_calendar();
			},
			'permission_callback' 	=> '__return_true',
			'args'					=> array(
				'month'		=> array(
					'required'	=> true,
					'validate_callback' => function($param, $request, $key) {
						return is_numeric( $param );
					}
				),
				'year'		=> array(
					'required'	=> true,
					'validate_callback' => function($param, $request, $key) {
						return is_numeric( $param );
					}
				),
			)
		)
	);

	// Week calendar
	register_rest_route( 
		'sim/v1/events', 
		'/get_week_html', 
		array(
			'methods' 				=> 'POST',
			'callback' 				=> function (){
				$events		= new Events();
				return $events->week_calendar();
			},
			'permission_callback' 	=> '__return_true',
			'args'					=> array(
				'wknr'		=> array(
					'required'	=> true,
					'validate_callback' => 'is_numeric'
				),
				'year'		=> array(
					'required'	=> true,
					'validate_callback' => 'is_numeric'
				),
			)
		)
	);

	// List calendar
	register_rest_route( 
		'sim/v1/events', 
		'/get_list_html', 
		array(
			'methods' 				=> 'POST',
			'callback' 				=> function(){
				$events		= new Events();
				return $events->list_calendar();
			},
			'permission_callback' 	=> '__return_true',
			'args'					=> array(
				'offset'		=> array(
					'required'	=> true,
					'validate_callback' => 'is_numeric'
				),
			)
		)
	);
} );