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
				$events		= new DisplayEvents();
				return $events->monthCalendar();
			},
			'permission_callback' 	=> '__return_true',
			'args'					=> array(
				'month'		=> array(
					'required'	=> true,
					'validate_callback' => function($param) {
						return is_numeric( $param );
					}
				),
				'year'		=> array(
					'required'	=> true,
					'validate_callback' => function($param) {
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
				$events		= new DisplayEvents();
				return $events->weekCalendar();
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
			'methods' 				=> 'POST,GET',
			'callback' 				=> function(){
				$events		= new DisplayEvents();
				return $events->listCalendar();
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

	// Upcoming events
	register_rest_route( 
		'sim/v1/events', 
		'/upcoming_events', 
		array(
			'methods' 				=> 'POST,GET',
			'callback' 				=> function(){
				$events		= new DisplayEvents();

				$items	= 10;
				$months	= 3;
				$cats	= [];

				if(!empty($_GET['items']) && is_numeric($_GET['items'])){
					$items	= $_GET['items'];
				}

				if(!empty($_GET['months']) && is_numeric($_GET['months'])){
					$months	= $_GET['months'];
				}

				if(!empty($_GET['cats'])){
					$cats	= explode(',', trim($_GET['categories'], ','));

					$categories	= get_categories( array(
						'taxonomy'		=> 'events',
						'hide_empty' 	=> false,
					) );
				
					$exclude	= $cats;
				
					$include	= [];
				
					foreach($categories as $category){
						if(!isset($exclude[$category->term_id])){
							$include[]	= $category->term_id;
						}
					}

				}
				return $events->upcomingEventsArray($items, $months, $include);
			},
			'permission_callback' 	=> '__return_true',
		)
	);
} );