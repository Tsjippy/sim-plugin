<?php
namespace SIM\EVENTS;
use SIM;

add_shortcode("schedules",function(){
	$schedule	= new Schedule();
	return $schedule->showschedules();
});