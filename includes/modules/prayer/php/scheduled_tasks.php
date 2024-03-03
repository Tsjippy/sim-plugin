<?php
namespace SIM\PRAYER;
use SIM;

add_action('init', function(){
	//add action for use in scheduled task
	add_action( 'send_prayer_action', __NAMESPACE__.'\sendPrayerRequests' );
	
	//add action for use in scheduled task
	add_action( 'check_prayer_action', __NAMESPACE__.'\checkPrayerRequests' );
});

function scheduleTasks(){
    SIM\scheduleTask('send_prayer_action', 'quarterly');

	SIM\scheduleTask('check_prayer_action', 'daily');
}

function createNewSchedule($schedule){

	if($schedule !== false){
		return $schedule;
	}

	// add the new schedule
	$schedule		= (array)get_option('signal_prayers');
	$updated		= false;
	foreach($schedule as $index=>$slot){
		if(empty($slot)){
			unset($schedule[$index]);
			$updated	= true;
		}
	}

	if($updated){
		update_option('signal_prayers', $schedule);
	}

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
	//Change the user to the admin account otherwise get_users will not work
	wp_set_current_user(1);

	$prayerRequest	= prayerRequest(true);

	$message	 	= "The prayer request of today is:\n";
	$message 		.= $prayerRequest['message'];
	
	// Get the schedule for today
	$date			= \Date('y-m-d');
	$schedule		= get_option("prayer_schedule_$date");

	$schedule		= createNewSchedule($schedule);

	$time	= current_time('H:i');
	foreach($schedule as $t=>$users){
		if(is_array($users)){
			// Do not continue for times in the future
			if($t > $time){
				continue;
			}

			foreach($users as $user){
				$dayPart	= "morning";
				$hour		= current_time('H');
				if($hour > 11 && $hour < 18){
					$dayPart	= 'afternoon';
				}elseif($hour > 17){
					$dayPart	= 'evening';
				}elseif($hour < 4){
					$dayPart	= 'night';
				}

				if(is_numeric($user)){
					$userdata	= get_userdata($user);

					if(!$userdata){
						continue;
					}
					
					$dayPart	.= " ".$userdata->first_name;
				}
				$result	= SIM\trySendSignal("Good $dayPart,\n\n$message", $user, false, $prayerRequest['pictures']);
			}
		}

		unset($schedule[$t]);
	}

	update_option("prayer_schedule_$date", $schedule);
}

/**
 * Check if a prayer request needs an update
 */
function checkPrayerRequests(){
	global $wpdb;

	// clean up expired meta keys
	$query			= "DELETE FROM `{$wpdb->usermeta}` WHERE `meta_key` = 'pending-prayer-update' AND `meta_value` < ".time();

	$wpdb->query($query);

	// Add new ones

	$days			= SIM\getModuleOption('prayer', 'prayercheck');
	if(empty($days)){
		return;
	}

	$dateTime		= strtotime("+$days day", time());
	$dateString		= date('d-m-Y', $dateTime);
	$prayerRequest  = prayerRequest(true, true, );
	$exploded		= explode('-', $prayerRequest['message']);

	if(count($exploded) < 2){
		return;
	}
	
	$message 		= trim($exploded[1]);

	$signalMessage	= "Good day, $days days from now your prayer request will be send out\n\nPlease reply to me with an updated request if needed.\n\nThis is the request I have now:\n\n$message\n\nIt will be send on $dateString\n\nTo confirm the update start your reply with 'update prayer'";

	foreach($prayerRequest['users'] as $user){
		$timestamp	= SIM\trySendSignal($signalMessage, $user, false, $prayerRequest['pictures']);

		update_user_meta($user->ID, 'pending-prayer-update', $timestamp);
	}
}


// Remove scheduled tasks upon module deactivatio
add_action('sim_module_deactivated', function($moduleSlug){
	//module slug should be the same as grandparent folder name
	if($moduleSlug != MODULE_SLUG)	{
		return;
	}

	wp_clear_scheduled_hook( 'send_prayer_action' );

	wp_clear_scheduled_hook( 'check_prayer_action' );
});