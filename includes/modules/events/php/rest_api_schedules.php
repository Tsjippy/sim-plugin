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
				return $schedule->addSchedule($_POST['update']);
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
					'validate_callback' => function($scheduleId){
						return is_numeric($scheduleId);
					}
				),
				'schedule_target'		=> array(
					'required'	=> true,
					'validate_callback' => function($userId){
						return is_numeric($userId);
					}
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
					'validate_callback' => function($scheduleId){
						return is_numeric($scheduleId);
					}
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
				$schedules		= new CreateSchedule();

				if(is_array($_POST['date'])){
					$succesFull		= '';
					$unSuccesFull	= '';
					foreach($_POST['date'] as $date){
						$result	= $schedules->addHost($date);

						if(is_wp_error($result)){
							if(!empty($unSuccesFull)){
								$unSuccesFull		.= ' and ';
							}
							$unSuccesFull	.= date('d-m-Y', strtotime($date));
						}else{
							if(!empty($succesFull)){
								$succesFull		.= ' and ';
							}
							$succesFull	.= date('d-m-Y', strtotime($date));
						}
					}

					$schedule			= $schedules->findScheduleById($schedules->scheduleId);

					$msg	= '';
					if(!empty($succesFull)){
						$msg	.= "Succesfully added you as a host for $schedule->name on $succesFull";
					}
					if(!empty($unSuccesFull)){
						$msg	.= "Could not add you as a host for $schedule->name on $unSuccesFull";
					}

					return $msg;
				}
				return $schedules->addHost($_POST['date']);
			},
			'permission_callback' 	=> '__return_true',
			'args'					=> array(
				'schedule_id'		=> array(
					'required'	=> true,
					'validate_callback' => function($scheduleId){
						return is_numeric($scheduleId);
					}
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
				$schedule				= new CreateSchedule();
				$schedule->date			= $_POST['date'];
				$schedule->startTime	= $_POST['starttime'];
				return $schedule->removeHost();
			},
			'permission_callback' 	=> '__return_true',
			'args'					=> array(
				'schedule_id'		=> array(
					'required'			=> true,
					'validate_callback' => function($scheduleId){
						return is_numeric($scheduleId);
					}
				),
				'date'		=> array(
					'required'			=> true,
					'validate_callback' => function($param){
						return SIM\isDate($param);
					}
				),
				'starttime'		=> array(
					'required'			=> true,
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
					'validate_callback' => function($scheduleId){
						return is_numeric($scheduleId);
					}
				),
				'date'		=> array(
					'required'	=> true,
					'validate_callback' => function($param){
						return SIM\isDate($param);
					}
				),
				'starttime'		=> array(
					'required'	=> true,
					'validate_callback' => function($startTime){
						return is_numeric($startTime);
					}
				),
				'recipe_keyword'		=> array(
					'required'	=> true
				),
			)
		)
	);
} );