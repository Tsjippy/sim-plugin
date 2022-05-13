<?php
namespace SIM\EVENTS;
use SIM;

add_action('init', function(){
	//add action for use in scheduled task
	add_action( 'remove_old_events_action', __NAMESPACE__.'\remove_old_events');
	add_action( 'anniversary_check_action', __NAMESPACE__.'\anniversary_check');
	add_action( 'remove_old_schedules_action', __NAMESPACE__.'\removeOldSchedules');
});

function schedule_tasks(){
	SIM\schedule_task('anniversary_check_action', 'daily');
	SIM\schedule_task('remove_old_schedules_action', 'daily');

    $freq   = SIM\get_module_option('events', 'freq');

    if($freq)   SIM\schedule_task('remove_old_events_action', $freq);
}

//clean up events, in events table. Not the post
function remove_old_events(){
	global $wpdb;

	$max_age   = SIM\get_module_option('events', 'max_age');

	$events		= new Events();

	$query	= "DELETE FROM {$events->table_name} WHERE startdate<'".date('Y-m-d',strtotime("- $max_age"))."'";

	$expired_events	= $wpdb->get_results( $query);
	foreach($expired_events as $event){
		wp_delete_post($event->ID);

		$events->remove_db_rows($event->ID);
	}
}

function anniversary_check(){
	$events		= new Events();

	$events->retrieve_events(date('Y-m-d'),date('Y-m-d'));

	foreach($events->events as $event){
		$start_year	= get_post_meta($event->ID,'celebrationdate',true);
		if(!empty($start_year)){
			$userdata		= get_userdata($event->post_author);
			$first_name		= $userdata->first_name;
			$event_title	= $event->post_title;
			$partner_id		= SIM\has_partner($event->post_author);

			if($partner_id){
				$partnerdata	= get_userdata($partner_id);
				$couple_string	= $first_name.' & '.$partnerdata->display_name;
				$event_title	= trim(str_replace($couple_string,"", $event_title));
			}
			
			$event_title	= trim(str_replace($userdata->display_name,"", $event_title));

			$age	= SIM\get_age_in_words($start_year);

			SIM\try_send_signal("Hi $first_name,\nCongratulations with your $age $event_title!", $event->post_author);

			//If the author has a partner and this events applies to both of them
			if($partner_id and strpos($event->post_title, $couple_string)){
				SIM\try_send_signal("Hi {$partnerdata->first_name},\nCongratulations with your $event_title!", $partner_id);
			}
		}
	}
}

function removeOldSchedules(){
	$schedules	= new Schedule();
	$schedules->get_schedules();

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