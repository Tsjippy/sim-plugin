<?php
namespace SIM\EVENTS;
use SIM;

add_action('init', function(){
	//add action for use in scheduled task
	add_action( 'remove_old_events_action', __NAMESPACE__.'\removeOldEvents');
	add_action( 'anniversary_check_action', __NAMESPACE__.'\anniversaryCheck');
	add_action( 'remove_old_schedules_action', __NAMESPACE__.'\removeOldSchedules');
});

/**
 * Schedules the tasks for this module
 * 
*/
function scheduleTasks(){
	SIM\scheduleTask('anniversary_check_action', 'daily');
	SIM\scheduleTask('remove_old_schedules_action', 'daily');

    $freq   = SIM\getModuleOption('events', 'freq');

    if($freq)   SIM\scheduleTask('remove_old_events_action', $freq);
}

/**
 * Clean up events, in events table. Not the post
 * 
*/
function removeOldEvents(){
	global $wpdb;

	$maxAge   	= SIM\getModuleOption('events', 'max_age');

	$events		= new Events();

	$query		= "DELETE FROM {$events->tableName} WHERE startdate<'".date('Y-m-d', strtotime("- $maxAge"))."'";

	$expiredEvents	= $wpdb->get_results( $query);
	foreach($expiredEvents as $event){
		wp_delete_post($event->ID);

		$events->removeDbRows($event->ID);
	}
}

/**
 * Get all the events of today and check if they are an anniversary.
 * If so, send a concratulation message
 * 
*/
function anniversaryCheck(){
	$events		= new Events();

	// Get all the events of today
	$events->retrieveEvents(date('Y-m-d'), date('Y-m-d'));

	foreach($events->events as $event){
		$startYear	= get_post_meta($event->ID, 'celebrationdate', true);

		if(!empty($startYear)){
			$userData		= get_userdata($event->post_author);
			$firstName		= $userData->first_name;
			$eventTitle		= $event->post_title;
			$partnerId		= SIM\hasPartner($event->post_author);

			if($partnerId){
				$partnerData	= get_userdata($partnerId);
				$coupleString	= $firstName.' & '.$partnerData->display_name;
				$eventTitle		= trim(str_replace($coupleString, "", $eventTitle));
			}
			
			$eventTitle	= trim(str_replace($userData->display_name, "", $eventTitle));

			$age	= SIM\getAge($startYear);

			SIM\trySendSignal("Hi $firstName,\nCongratulations with your $age $eventTitle!", $event->post_author);

			//If the author has a partner and this events applies to both of them
			if($partnerId and strpos($event->post_title, $coupleString)){
				SIM\trySendSignal("Hi {$partnerData->first_name},\nCongratulations with your $eventTitle!", $partnerId);
			}
		}
	}
}

/**
 * Get all schedules with an enddate in the past and deletes them
*/
function removeOldSchedules(){
	$schedules	= new Schedule();
	$schedules->getSchedules();

	foreach($schedules->schedules as $schedule){
		if($schedule->enddate < date('Y-m-d')){
			$schedules->removeSchedule($schedule->id);
		}
	}
}

// Remove scheduled tasks upon module deactivatio
add_action('sim_module_deactivated', function($moduleSlug, $options){
	//module slug should be the same as grandparent folder name
	if($moduleSlug != basename(dirname(dirname(__FILE__))))	return;

	wp_clear_scheduled_hook( 'anniversary_check_action' );
	wp_clear_scheduled_hook( 'remove_old_events_action' );
}, 10, 2);