<?php
namespace SIM\EVENTS;
use SIM;

add_shortcode("schedules",function(){
	$schedule	= new Schedules();
	return $schedule->showSchedules();
});