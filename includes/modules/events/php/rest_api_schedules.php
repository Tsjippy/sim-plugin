<?php
namespace SIM\EVENTS;
use SIM;

add_action( 'rest_api_init', function () {
	//add_schedule
	register_rest_route(
		'sim/v1/events', 
		'/add_schedule', 
		array(
			'methods' 				=> 'POST',
			'callback' 				=> function(){
				$schedule		= new Schedule();
				return $schedule->add_schedule();
			},
			'permission_callback' 	=> function(){
				$schedule	= new Schedule();
				return $schedule->admin;
			},
			'args'					=> array(
				'target_name'		=> array(
					'required'	=> true
				),
				'startdate'		=> array(
					'required'	=> true,
				),
				'enddate'		=> array(
					'required'	=> true
				),
			)
		)
	);

	//publish_schedule
	register_rest_route( 
		'sim/v1/events', 
		'/publish_schedule', 
		array(
			'methods' 				=> 'POST',
			'callback' 				=> function(){
				$schedule		= new Schedule();
				return $schedule->publish_schedule();
			},
			'permission_callback' 	=> function(){
				$schedule	= new Schedule();
				return $schedule->admin;
			},
			'args'					=> array(
				'schedule_id'		=> array(
					'required'	=> true,
					'validate_callback' => 'is_numeric'
				),
				'schedule_target'		=> array(
					'required'	=> true,
					'validate_callback' => 'is_numeric'
				)
			)
		)
	);

	//remove_schedule
	register_rest_route( 
		'sim/v1/events', 
		'/remove_schedule', 
		array(
			'methods' 				=> 'POST',
			'callback' 				=> function(){
				$schedule		= new Schedule();
				return $schedule->removeSchedule($_POST['schedule_id']);
			},
			'permission_callback' 	=> function(){
				$schedule	= new Schedule();
				return $schedule->admin;
			},
			'args'					=> array(
				'schedule_id'		=> array(
					'required'	=> true,
					'validate_callback' => 'is_numeric'
				)
			)
		)
	);

	//add_host
	register_rest_route( 
		'sim/v1/events', 
		'/add_host', 
		array(
			'methods' 				=> 'POST',
			'callback' 				=> function(){
				$schedule		= new Schedule();
				return $schedule->add_host();
			},
			'permission_callback' 	=> '__return_true',
			'args'					=> array(
				'schedule_id'		=> array(
					'required'	=> true,
					'validate_callback' => 'is_numeric'
				),
				'host'		=> array(
					'required'	=> true,
					'validate_callback' => 'is_numeric'
				),
				'date'		=> array(
					'required'	=> true,
					'validate_callback' => function($param){
						return SIM\is_date($param);
					}
				),
				'starttime'		=> array(
					'required'	=> true,
					'validate_callback' => function($param){
						return SIM\is_time($param);
					}
				)
			)
		)
	);

	//remove_host
	register_rest_route( 
		'sim/v1/events', 
		'/remove_host', 
		array(
			'methods' 				=> 'POST',
			'callback' 				=> function(){
				$schedule		= new Schedule();
				return $schedule->remove_host();
			},
			'permission_callback' 	=> '__return_true',
			'args'					=> array(
				'schedule_id'		=> array(
					'required'	=> true,
					'validate_callback' => 'is_numeric'
				),
				'host'		=> array(
					'required'	=> true,
					'validate_callback' => 'is_numeric'
				),
				'date'		=> array(
					'required'	=> true,
					'validate_callback' => function($param){
						return SIM\is_date($param);
					}
				),
				'starttime'		=> array(
					'required'	=> true,
					'validate_callback' => function($param){
						return SIM\is_time($param);
					}
				)
			)
		)
	);

	//add_menu
	register_rest_route( 
		'sim/v1/events', 
		'/add_menu', 
		array(
			'methods' 				=> 'POST',
			'callback' 				=> function(){
				$schedule		= new Schedule();
				return $schedule->add_menu();
			},
			'permission_callback' 	=> '__return_true',
			'args'					=> array(
				'schedule_id'		=> array(
					'required'	=> true,
					'validate_callback' => 'is_numeric'
				),
				'date'		=> array(
					'required'	=> true,
					'validate_callback' => function($param){
						return SIM\is_date($param);
					}
				),
				'starttime'		=> array(
					'required'	=> true,
					'validate_callback' => 'is_numeric'
				),
				'recipe_keyword'		=> array(
					'required'	=> true
				),
			)
		)
	);
} );