<?php
namespace SIM\EVENTS;
use SIM;

add_action( 'rest_api_init', function () {
	//add_schedule
	register_rest_route(
		RESTAPIPREFIX.'/events', 
		'/add_schedule', 
		array(
			'methods' 				=> 'POST',
			'callback' 				=> function(){
				$schedule		= new CreateSchedule();
				return $schedule->addSchedule();
			},
			'permission_callback' 	=> function(){
				$schedule	= new CreateSchedule();
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
		RESTAPIPREFIX.'/events', 
		'/publish_schedule', 
		array(
			'methods' 				=> 'POST',
			'callback' 				=> function(){
				$schedule		= new CreateSchedule();
				return $schedule->publishSchedule();
			},
			'permission_callback' 	=> function(){
				$schedule	= new CreateSchedule();
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
		RESTAPIPREFIX.'/events', 
		'/remove_schedule', 
		array(
			'methods' 				=> 'POST',
			'callback' 				=> function(){
				$schedule		= new CreateSchedule();
				return $schedule->removeSchedule($_POST['schedule_id']);
			},
			'permission_callback' 	=> function(){
				$schedule	= new CreateSchedule();
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
		RESTAPIPREFIX.'/events', 
		'/add_host', 
		array(
			'methods' 				=> 'POST',
			'callback' 				=> function(){
				$schedule		= new CreateSchedule();
				return $schedule->addHost();
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
						return SIM\isDate($param);
					}
				),
				'starttime'		=> array(
					'required'	=> true,
					'validate_callback' => function($param){
						return SIM\isTime($param);
					}
				)
			)
		)
	);

	//remove_host
	register_rest_route( 
		RESTAPIPREFIX.'/events', 
		'/remove_host', 
		array(
			'methods' 				=> 'POST',
			'callback' 				=> function(){
				$schedule		= new CreateSchedule();
				return $schedule->removeHost();
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
						return SIM\isDate($param);
					}
				),
				'starttime'		=> array(
					'required'	=> true,
					'validate_callback' => function($param){
						return SIM\isTime($param);
					}
				)
			)
		)
	);

	//add_menu
	register_rest_route( 
		RESTAPIPREFIX.'/events', 
		'/add_menu', 
		array(
			'methods' 				=> 'POST',
			'callback' 				=> function(){
				$schedule		= new CreateSchedule();
				return $schedule->addMenu();
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
						return SIM\isDate($param);
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