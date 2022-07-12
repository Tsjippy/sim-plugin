<?php
namespace SIM\EVENTS;
use SIM;

add_shortcode("schedules",function(){
	$schedule	= new Schedules();
	return $schedule->showSchedules();
});

add_shortcode("upcomingevents", function(){
	if(!is_page(SIM\getModuleOption('frontpage', 'home_page'))){
		return;
	}

	wp_enqueue_style('sim_events_css');

	$events	= new DisplayEvents();
	return $events->upcomingEvents();
});