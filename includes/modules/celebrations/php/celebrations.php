<?php
namespace SIM;

function get_anniversaries(){
	global $Modules;
	if(!isset($Modules['extra_post_types']['event']))	return [];

	global $Events;

	$messages = [];

	$Events->retrieve_events(date('Y-m-d'),date('Y-m-d'));

	foreach($Events->events as $event){
		$start_year	= get_post_meta($event->ID,'celebrationdate',true);
		if(!empty($start_year)){
			$title	= $event->post_title;
			$age	= get_age_in_words($start_year);
			$privacy= (array)get_user_meta($event->post_author, 'privacy_preference', true);

			if(substr($title,0,8) == 'Birthday' and in_array('hide_age', $privacy)){
				$age	= '';
			}
			if(substr($title,0,3) != 'SIM'){
				$title	= lcfirst($title);
			}
			$messages[$event->post_author] = trim("$age $title");
		}
	}

	return $messages;
}