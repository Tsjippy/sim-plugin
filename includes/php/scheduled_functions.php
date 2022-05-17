<?php
namespace SIM;

function schedule_task($taskname, $recurrence){
	// Clear before readding
	if (wp_next_scheduled($taskname)) {
		wp_clear_scheduled_hook( $taskname );
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
			$monthcount = 0;
			$month		= 0;
			while(!in_array($month, [1,4,7,10])){
				$monthcount++;
				$time	= strtotime("first day of +$monthcount month");
				$month = date('n',$time);
			}
			break;
		case 'sixmonthly':
				//calculate start of next half year
				$monthcount = 0;
				$month		= 0;
				while(!in_array($month, [1,7])){
					$monthcount++;
					$time	= strtotime("first day of +$monthcount month");
					$month	= date('n',$time);
				}
				break;
		case 'yearly':
			$time	= strtotime('first day of next year');
			break;
		default:
			$time	= time();
	} 

	//schedule
	if(wp_schedule_event( $time, $recurrence, $taskname )){
		printArray("Succesfully scheduled $taskname to run $recurrence");
	}else{
		printArray("Scheduling of $taskname unsuccesfull");
	}
}

//Adds extra schedule recurrences
add_filter( 'cron_schedules', function ( $schedules ) {
   // Adds once monthly to the existing schedules.
   $schedules['monthly'] = array(
       'interval'	=> 2628000,
       'display' 	=> __( 'Once every month' )
   );
   
   // Adds threemonthly to the existing schedules.
   $schedules['threemonthly'] = array(
       'interval' => 7884000,
       'display' => __( 'Once every 3 months' )
   );

   // Adds sixmonthly to the existing schedules.
   $schedules['sixmonthly'] = array(
		'interval'	=> 60*60*24*182,
		'display'	=> __( 'Once every 6 months' )
	);

   // Adds yearly to the existing schedules.
	$schedules['yearly'] = array(
		'interval' => 31557600,
		'display' => __( 'Once every year' )
	);
   return $schedules;
});
