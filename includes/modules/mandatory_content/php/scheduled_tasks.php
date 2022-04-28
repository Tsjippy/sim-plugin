<?php
namespace SIM\MANDATORY;
use SIM;

add_action('init', function(){
	//add action for use in scheduled task
	add_action( 'read_reminder_action', 'SIM\MANDATORY\read_reminder' );
});

function schedule_tasks(){
    $freq   = SIM\get_module_option('mandatory_content', 'reminder_freq');
    if($freq)   SIM\schedule_task('read_reminder_action', $freq);
}

function read_reminder(){	
	//Change the user to the adminaccount otherwise get_users will not work
	wp_set_current_user(1);
	
	$users = SIM\get_user_accounts();
	foreach($users as $user){
		$html = mustReadDocuments($user->ID);
		
		//Only continue if there are documents to read
		if(!empty($html)){
			$to = $user->user_email;
				
			//Skip if not valid email
			if(strpos($to,'.empty') !== false) continue;

			//Send Signal message
			SIM\try_send_signal("Hi $user->first_name,\nPlease read some mandatory content.\n\nVisit ".SITEURL." to see the content",$user->ID);
			
			//Send e-mail
			$readReminder    = new ReadReminder($user, $html);
			$readReminder->filterMail();
								
			wp_mail( $user->user_email, $readReminder->subject, $readReminder->message);
		}
	}
}

// Remove scheduled tasks upon module deactivatio
add_action('sim_module_deactivated', function($module_slug, $options){
	//module slug should be the same as grandparent folder name
	if($module_slug != basename(dirname(dirname(__FILE__))))	return;

	wp_clear_scheduled_hook( 'read_reminder_action' );
}, 10, 2);