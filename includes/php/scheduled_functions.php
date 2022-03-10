<?php
namespace SIM;

add_action('admin_init', function() {
    if ( is_admin() && get_option( 'Activated_Plugin' ) == 'SIM' ) {
		delete_option( 'Activated_Plugin' );

		add_cron_schedules();
    }
});

//Add action to scan for old pages reminder
add_action('init', function () {
	add_action( 'check_last_login_date_action', 'SIM\check_last_login_date' );
	add_action( 'process_images_action', 'SIM\process_images' );
});

function add_cron_schedules(){
	print_array("Adding cron schedules");

	schedule_task('check_last_login_date_action', 'monthly');
	schedule_task('process_images_action', 'daily');
}

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
		print_array("Succesfully scheduled $taskname to run $recurrence");
	}else{
		print_array("Scheduling of $taskname unsuccesfull");
	}
}

//Send reminder to people to login
function check_last_login_date(){
	$users = get_user_accounts();
	foreach($users as $user){
		$lastlogin = get_user_meta( $user->ID, 'last_login_date',true);
		$lastlogin_date	= date_create($lastlogin);
		$now 	= new \DateTime();
		$years_since_last_login = date_diff($lastlogin_date,$now)->format("%y");
		
		//User has not logged in in the last year
		if($years_since_last_login > 0){
			//Send e-mail
			$to = $user->user_email;
			//Skip if not valid email
			if(strpos($to,'.empty') !== false) continue;

			//Send Signal message
			try_send_signal(
				"Hi $user->first_name,\n\nWe miss you! We haven't seen you since $lastlogin\n\nPlease pay us a visit on\n".SITEURL,
				$user->ID
			);
			
			//Send e-mail
			$subject 	 = "We miss you!";
			$message 	 = 'Hi '.$user->first_name.',<br><br>';
			$message 	.= "We miss you! We haven't seen you since $lastlogin<br>";
			$message 	.= 'Please pay us a visit on <a href="'.SITEURL.'">'.SITENAME.'</a><br>';
			
			//Send the mail
			wp_mail($to , $subject, $message );
		}
	}
	
}

//Creates subimages
function process_images(){
	include_once( ABSPATH . 'wp-admin/includes/image.php' );
	$images = get_posts(array(
		'numberposts'      => -1,
		'post_type'        => 'attachment',
	));
	
	foreach($images as $image){
		wp_maybe_generate_attachment_metadata($image);
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
