<?php
namespace SIM\EVENTS;
use SIM;

add_action('init', function(){
	//add action for use in scheduled task
	add_action( 'remove_old_events_action', 'SIM\EVENTS\remove_old_events');
});

function schedule_tasks(){
    $freq   = SIM\get_module_option('events', 'freq');

    if($freq)   SIM\schedule_task('remove_old_events_action', $freq);
}

//clean up events, in events table. Not the post
function remove_old_events(){
	global $Events;
	global $wpdb;

	$max_age   = SIM\get_module_option('events', 'max_age');

	$query	= "DELETE FROM {$Events->table_name} WHERE startdate<'".date('Y-m-d',strtotime("- $max_age"))."'";

	$expired_events	= $wpdb->get_results( $query);
	foreach($expired_events as $event){
		wp_delete_post($event->ID);

		$Events->remove_db_rows($event->ID);
	}
}