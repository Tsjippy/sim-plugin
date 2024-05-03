<?php
namespace SIM\MANDATORY;
use SIM;

add_action('init', function(){
	//add action for use in scheduled task
	add_action( 'read_reminder_action', __NAMESPACE__.'\readReminder' );
});

function scheduleTasks(){
    $freq   = SIM\getModuleOption(MODULE_SLUG, 'reminder_freq');
    if($freq){
		SIM\scheduleTask('read_reminder_action', $freq);
	}
}

/**
 * Send an e-mail to remind people to read their mandatory content
 */
function readReminder(){
	//Change the user to the adminaccount otherwise get_users will not work
	wp_set_current_user(1);
	
	$users = SIM\getUserAccounts();
	foreach($users as $user){
		$html = mustReadDocuments($user->ID);
		
		//Only continue if there are documents to read
		if(!empty($html)){
			$to = $user->user_email;
				
			//Skip if not valid email
			if(str_contains($to,'.empty')){
				continue;
			}

			//Send Signal message
			SIM\trySendSignal("Hi $user->first_name,\nPlease read some mandatory content.\n\nVisit ".SITEURL." to see the content",$user->ID);
			
			//Send e-mail
			$readReminder    = new ReadReminder($user, $html);
			$readReminder->filterMail();
								
			wp_mail( $user->user_email, $readReminder->subject, $readReminder->message);
		}
	}
}

// Remove scheduled tasks upon module deactivatio
add_action('sim_module_deactivated', function($moduleSlug){
	//module slug should be the same as grandparent folder name
	if($moduleSlug != MODULE_SLUG)	{return;}

	wp_clear_scheduled_hook( 'read_reminder_action' );
});