<?php
namespace SIM\EVENTS;
use SIM;

add_shortcode("schedules",function(){
	$schedule	= new Schedule();
	return $schedule->showschedules();
});

add_shortcode("upcomingevents", function(){
	if(!is_page(SIM\get_module_option('login', 'home_page'))) return;

	wp_enqueue_style('sim_events_css');

	$events	= new Events();
	return $events->upcomingevents();
});