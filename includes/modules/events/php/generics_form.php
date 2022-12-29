<?php
namespace SIM\EVENTS;
use SIM;

//create  events
add_filter('sim_before_saving_formdata', function($formResults, $formName, $userId){
	if($formName != 'user_generics'){
		return $formResults;
	}
	
	$events	= new CreateEvents();
	$events->createCelebrationEvent('birthday', $userId, 'birthday', $_POST['birthday']);
	$events->createCelebrationEvent(SITENAME.' anniversary', $userId, 'arrival_date', $_POST['arrival_date']);
	
	return $formResults;
}, 10, 3);