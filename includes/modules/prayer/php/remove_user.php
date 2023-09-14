<?php
namespace SIM\PRAYER;
use SIM;

add_action('delete_user', function ($userId){
	$schedule		= (array)get_option('signal_prayers');
	$updated		= false;

	// loop over all the timeslots
	foreach($schedule as $index=>$slot){
		if(is_array($slot)){
			// loop over all the user id's in this time slot
			foreach($slot as $i=>$id){
				if($id == $userId){
					// remove this userid from the timeslot
					unset($schedule[$index][$i]);
					$updated	= true;
				}
			}
		}
	}

	if($updated){
		update_option('signal_prayers', $schedule);
	}
});