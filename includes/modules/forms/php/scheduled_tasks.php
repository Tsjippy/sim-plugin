<?php
namespace SIM\FORMS;
use SIM;

add_action('init', function(){
	//add action for use in scheduled task
	add_action( 'auto_archive_action', __NAMESPACE__.'\autoArchiveFormEntries' );
    add_action( 'mandatory_fields_reminder_action', __NAMESPACE__.'\mandatoryFieldsReminder' );
});

function scheduleTasks(){
    SIM\scheduleTask('auto_archive_action', 'daily');

    $freq   = SIM\getModuleOption('forms', 'reminder_freq');
    if($freq){
        SIM\scheduleTask('mandatory_fields_reminder_action', $freq);
    }
}

function autoArchiveFormEntries(){
	$editFormResults = new EditFormResults();
	$editFormResults->autoArchive();
}

//loop over all users and scan for missing info
function mandatoryFieldsReminder(){
	//Change the user to the admin account otherwise get_users will not work
	wp_set_current_user(1);
	
	//Retrieve all users
	$users = SIM\getUserAccounts(false, true, true);
	//Loop over the users
 	foreach($users as $user){
		//get the reminders for this user
		$reminderHtml = getAllFields($user->ID, 'mandatory');
		//If there are reminders, send an e-mail
		if (!empty($reminderHtml)){
			$recipients     = '';
            $parents        = SIM\getParents($user->ID);
            //Is child
            if($parents){
                $childTitle    = SIM\getChildTitle($user->ID);

                $childEmail    = new ChildEmail($user);
                $childEmail->filterMail();
                    
                $subject        = $childEmail->subject;
                $message        = $childEmail->message;

                $reminderHtml  = str_replace("Your",$user->first_name."'s", $reminderHtml);
                
                foreach($parents as $parent){
                    if(strpos($parent->user_email,'.empty') === false){
                        if(!empty($recipients)){
                            $recipients .= ', ';
                        }
                        $recipients .= $parent->user_email;
                    }
                                
                    //Send Signal message
                    SIM\trySendSignal(
                        "Hi $parent->first_name,\nPlease update the personal information of your $childTitle $user->first_name here:\n\n".SITEURL."/account",
                        $user->ID
                    );
                }				
            //not a child
            }else{			
                //Send Signal message
                SIM\trySendSignal(
                    "Hi $user->first_name,\nPlease update your personal information here:\n\n".SITEURL."/account",
                    $user->ID
                );
                
                //If this not a valid email skip this email
                if(strpos($user->user_email,'.empty') !== false){
                    continue;
                }

                $adultEmail    = new AdultEmail($user);
                $adultEmail->filterMail();
                    
                $subject        = $adultEmail->subject;
                $message        = $adultEmail->message;
                $recipients	    = $user->user_email;
            }
            
            //If there is an email set
            if(!empty($recipients)){                
                $message .= $reminderHtml;
                
                wp_mail( $recipients, $subject, $message);
            }
		}
	} 
}

// Remove scheduled tasks upon module deactivatio
add_action('sim_module_deactivated', function($moduleSlug){
	//module slug should be the same as grandparent folder name
	if($moduleSlug != MODULE_SLUG)	{return;}

	wp_clear_scheduled_hook( 'auto_archive_action' );
	wp_clear_scheduled_hook( 'mandatory_fields_reminder_action' );
});