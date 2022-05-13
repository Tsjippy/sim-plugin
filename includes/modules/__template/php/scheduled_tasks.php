<?php
namespace SIM\ADMIN;
use SIM;

add_action('init', function(){
	//add action for use in scheduled task
	add_action( 'auto_archive_action', __NAMESPACE__.'\auto_archive_form_entries' );
    add_action( 'mandatory_fields_reminder_action', __NAMESPACE__.'\mandatory_fields_reminder' );
});

function schedule_tasks(){
    SIM\schedule_task('auto_archive_action', 'daily');

    $freq   = SIM\get_module_option('forms', 'warning_freq');
    if($freq)   SIM\schedule_task('mandatory_fields_reminder_action', $freq);
}

// Remove scheduled tasks upon module deactivatio
add_action('sim_module_deactivated', function($moduleSlug, $options){
	//module slug should be the same as grandparent folder name
	if($moduleSlug != basename(dirname(dirname(__FILE__))))	return;

	wp_clear_scheduled_hook( 'auto_archive_action' );
	wp_clear_scheduled_hook( 'mandatory_fields_reminder_action' );
}, 10, 2);