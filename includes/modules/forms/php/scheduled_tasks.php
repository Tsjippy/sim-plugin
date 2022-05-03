<?php
namespace SIM\FORMS;
use SIM;

add_action('init', function(){
	//add action for use in scheduled task
	add_action( 'auto_archive_action', __NAMESPACE__.'\auto_archive_form_entries' );
    add_action( 'mandatory_fields_reminder_action', __NAMESPACE__.'\mandatory_fields_reminder' );
});

function schedule_tasks(){
    SIM\schedule_task('auto_archive_action', 'daily');

    $freq   = SIM\get_module_option('forms', 'reminder_freq');
    if($freq)   SIM\schedule_task('mandatory_fields_reminder_action', $freq);
}

function auto_archive_form_entries(){
	$formbuilder = new Formbuilder();
	$formbuilder->autoarchive();
}

//loop over all users and scan for missing info
function mandatory_fields_reminder(){
	//Change the user to the admin account otherwise get_users will not work
	wp_set_current_user(1);
	
	//Retrieve all users
	$users = SIM\get_user_accounts(false,true,true);
	//Loop over the users
 	foreach($users as $user){
		//get the reminders for this user
		$reminder_html = getAllFields($user->ID, 'mandatory');
		//If there are reminders, send an e-mail
		if (!empty($reminder_html)){
			$recipients = '';
            $parents = SIM\get_parents($user->ID);
            //Is child
            if(count($parents)>0){
                $child_title    = SIM\get_child_title($user->ID);

                $childEmail    = new ChildEmail($user);
                $childEmail->filterMail();
                    
                $subject        = $childEmail->subject;
                $message        = $childEmail->message;

                $reminder_html  = str_replace("Your",$user->first_name."'s",$reminder_html);
                
                foreach($parents as $parent){
                    if(strpos($parent->user_email,'.empty') === false){
                        if($recipients != '') $recipients .= ', ';
                        $recipients .= $parent->user_email;
                    }
                                
                    //Send Signal message
                    SIM\try_send_signal(
                        "Hi $parent->first_name,\nPlease update the personal information of your $child_title $user->first_name here:\n\n".SITEURL."/account",
                        $user->ID
                    );
                }				
            //not a child
            }else{			
                //Send Signal message
                SIM\try_send_signal(
                    "Hi $user->first_name,\nPlease update your personal information here:\n\n".SITEURL."/account",
                    $user->ID
                );
                
                //If this not a valid email skip this email
                if(strpos($user->user_email,'.empty') !== false) continue;

                $adultEmail    = new AdultEmail($user);
                $adultEmail->filterMail();
                    
                $subject        = $adultEmail->subject;
                $message        = $adultEmail->message;
                $recipients	    = $user->user_email;
            }
            
            //If there is an email set
            if(!empty($recipients)){                
                $message .= $reminder_html;
                
                wp_mail( $recipients, $subject, $message);
            }
		}
	} 
}

// Remove scheduled tasks upon module deactivatio
add_action('sim_module_deactivated', function($module_slug, $options){
	//module slug should be the same as grandparent folder name
	if($module_slug != basename(dirname(dirname(__FILE__))))	return;

	wp_clear_scheduled_hook( 'auto_archive_action' );
	wp_clear_scheduled_hook( 'mandatory_fields_reminder_action' );
}, 10, 2);