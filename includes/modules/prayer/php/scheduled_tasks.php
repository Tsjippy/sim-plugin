<?php
namespace SIM\PRAYER;
use SIM;

add_action('init', function(){
	//add action for use in scheduled task
	add_action( 'send_prayer_action', __NAMESPACE__.'\sendPrayerRequests' );
});

function scheduleTasks(){
    SIM\scheduleTask('send_prayer_action', 'quarterly');
}

function createNewSchedule($schedule){
	if(!empty($schedule)){
		return $schedule;
	}

	// add the new schedule
	$prayerTimes    = (array)get_option('signal_prayers');
	$schedule		= $prayerTimes;

	$groups			= SIM\getModuleOption(MODULE_SLUG, 'groups');
	foreach($groups as $group){
		if(isset($schedule[$group['time']])){
			$schedule[$group['time']][]	= $group['name'];
		}else{
			$schedule[$group['time']]	= [$group['name']];
		}
	}

	// remove the old schedule
	$yesterday	= date('Y-m-d', strtotime('-1 day'));
	delete_option("prayer_schedule_$yesterday");

	return $schedule;
}

/**
 * We will send the prayer request based on the times as given by people
 * As we are not sure about the timeliness of the cron schedule we keep
 * a seperate schedule for each day to be sure everyone gets what they requested
 */
function sendPrayerRequests(){
	//Change the user to the adminaccount otherwise get_users will not work
	wp_set_current_user(1);

	$request	= prayerRequest(true);
	
	// Get the schedule for today
	$date			= \Date('y-m-d');
	$schedule		= get_option("prayer_schedule_$date");
	
	createNewSchedule($schedule);

	$time	= current_time('H:i');
	foreach($schedule as $t=>$users){
		if(is_array($users)){
			// Do not continue for times in the future
			if($t > $time){
				break;
			}

			foreach($users as $user){
				SIM\trySendSignal($request, $user);
			}
		}

		unset($schedule[$t]);
	}

	update_option("prayer_schedule_$date", $schedule);
}


// Remove scheduled tasks upon module deactivatio
add_action('sim_module_deactivated', function($moduleSlug){
	//module slug should be the same as grandparent folder name
	if($moduleSlug != MODULE_SLUG)	{
		return;
	}

	wp_clear_scheduled_hook( 'send_prayer_action' );
});