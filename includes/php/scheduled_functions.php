<?php
namespace SIM;

/**
 * Schedule a function
 * @param	string 		$taskName			The name to be used for the task
 * @param	string		$recurrence			The recurence one of: weekly, monthly, threemonthly, sixmonthly,yearly. Default daily
*/
function scheduleTask($taskName, $recurrence){
	// Clear before re-adding
	if (wp_next_scheduled($taskName)) {
		wp_clear_scheduled_hook( $taskName );
	}

	switch ($recurrence) {
		case 'weekly':
			$time	= strtotime('next Monday');
			break;
		case 'monthly':
			$time	= strtotime('first day of next month');
			break;
		case 'threemonthly':
			//calculate start of next quarter
			$monthCount = 0;
			$month		= 0;
			while(!in_array($month, [1,4,7,10])){
				$monthCount++;
				$time	= strtotime("first day of +$monthCount month");
				$month = date('n', $time);
			}
			break;
		case 'sixmonthly':
				//calculate start of next half year
				$monthCount = 0;
				$month		= 0;
				while(!in_array($month, [1,7])){
					$monthCount++;
					$time	= strtotime("first day of +$monthCount month");
					$month	= date('n', $time);
				}
				break;
		case 'yearly':
			$time	= strtotime('first day of next year');
			break;
		default:
			$time	= time();
	} 

	//schedule
	if(wp_schedule_event( $time, $recurrence, $taskName )){
		printArray("Succesfully scheduled $taskName to run $recurrence");
	}else{
		printArray("Scheduling of $taskName unsuccesfull");
	}
}

//Adds extra schedule recurrences
add_filter( 'cron_schedules', __NAMESPACE__.'\addCronSchedule');
function addCronSchedule( $schedules ) {
	// Adds once every 15 minutes to the existing schedules.
	$schedules['quarterly'] = array(
		'interval'	=> 900,
		'display' 	=> __( 'Once every 15 minutes', 'sim-plugin')
	);

   // Adds once monthly to the existing schedules.
   $schedules['monthly'] = array(
       'interval'	=> 2628000,
       'display' 	=> __( 'Once every month', 'sim-plugin' )
   );
   
   // Adds threemonthly to the existing schedules.
   $schedules['threemonthly'] = array(
       'interval' => 7884000,
       'display' => __( 'Once every 3 months', 'sim-plugin' )
   );

   // Adds sixmonthly to the existing schedules.
   $schedules['sixmonthly'] = array(
		'interval'	=> 60*60*24*182,
		'display'	=> __( 'Once every 6 months', 'sim-plugin' )
	);

   // Adds yearly to the existing schedules.
	$schedules['yearly'] = array(
		'interval' => 31557600,
		'display' => __( 'Once every year', 'sim-plugin' )
	);

	return $schedules;
}
