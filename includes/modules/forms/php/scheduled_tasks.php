<?php
namespace SIM\FORMS;
use SIM;

add_action('init', function(){
	//add action for use in scheduled task
	add_action( 'auto_archive_action', __NAMESPACE__.'\autoArchiveFormEntries' );
    
	add_action( 'form_reminder_action', __NAMESPACE__.'\formReminder' );

    add_action( 'mandatory_fields_reminder_action', __NAMESPACE__.'\mandatoryFieldsReminder' );
});

function scheduleTasks(){
    SIM\scheduleTask('auto_archive_action', 'daily');
    
    SIM\scheduleTask('form_reminder_action', 'daily');

    $freq   = SIM\getModuleOption(MODULE_SLUG, 'reminder_freq');
    if($freq){
        SIM\scheduleTask('mandatory_fields_reminder_action', $freq);
    }
}

function autoArchiveFormEntries(){
	$editFormResults = new EditFormResults();
	$editFormResults->autoArchive();
}

/**
 * Sends reminders by e-mail and Signal to fill in a form
 */
function formReminder(){
    // Also send a reminder for any mandatory forms
    $simForms   = new SubmitForm();

    foreach(getAllRequiredForms() as $formId=>$userIds){
        $simForms->getForm($formId);
        $emails = $simForms->formData->emails;
		
		foreach($emails as $mail){
            if($mail['emailtrigger'] == 'shouldsubmit'){

                $from       = $mail['from'];

                $to         = $mail['to'];

                $subject    = $mail['subject'];

                $message    = $mail['message'];

                $headers	= [];

                if(!empty(trim($mail['headers']))){
                    $headers	= explode("\n", trim($mail['headers']));
                }

                // Send an e-mail to each user
                foreach($userIds as $userId){
                    $user   = get_userdata($userId);

                    if(!empty($from)){
                        if(str_contains($from, '%')){
                            $headers[]	= "Reply-To: ". $user->user_email;
                        }else{
                            $headers[]	= "Reply-To: $from";
                        }
                    }

                    if(str_contains($to, '%')){
                        $recipient  = $user->user_email; 
                    }else{
                        $recipient  = $to;
                    }

                    $m      = "Hi $user->first_name,<br><br>";
                    $m      .= $simForms->processPlaceholders(
                        $message,
                        [
                            'formurl'   => $simForms->formData->form_url,
                            'name'      => $user->first_name,
                            'email'     => $user->user_email,
                        ]
                    );

                    $result = wp_mail($recipient , $subject, $m, $headers);

                    // Find any hyperlinks in the text
                    preg_match_all('/<a\s+href=(?:"|\')(.*?)(?:"|\')>(.*?)<\/a>/i', $m, $matches);

                    //replace the hyperlinks with plain links
                    foreach($matches[0] as $index=>$match){
                        $m  = str_replace($match, $matches[2][$index].': '.str_replace('https://', '', $matches[1][$index]), $m);
                    }

                    //Send Signal message
                    SIM\trySendSignal(
                        html_entity_decode(strip_tags(str_replace(['<br>', '</br>', '<br />', '</p>'], "\n", $m))),
                        $user->ID
                    );
                }
            }
        }
    }
}

//loop over all users and scan for missing info
function mandatoryFieldsReminder(){
	//Change the user to the admin account otherwise get_users will not work
	wp_set_current_user(1);
	
	//Retrieve all users
	$users = SIM\getUserAccounts(false, true, true);

    $accountUrl		= SIM\ADMIN\getDefaultPageLink('usermanagement', 'account_page');
		
	//Loop over the users
 	foreach($users as $user){
		//get the reminders for this user
		$reminderHtml = getAllEmptyRequiredElements($user->ID, 'mandatory');

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

                $reminderHtml  = str_replace("Your", $user->first_name."'s", $reminderHtml);
                
                foreach($parents as $parent){
                    if(!str_contains($parent->user_email,'.empty')){
                        if(!empty($recipients)){
                            $recipients .= ', ';
                        }
                        $recipients .= $parent->user_email;
                    }
                                
                    //Send Signal message
                    SIM\trySendSignal(
                        "Hi $parent->first_name,\nPlease update the personal information of your $childTitle $user->first_name here:\n\n$accountUrl",
                        $user->ID
                    );
                }
            //not a child
            }else{
                //Send Signal message
                SIM\trySendSignal(
                    "Hi $user->first_name,\nPlease update your personal information here:\n\n$accountUrl",
                    $user->ID
                );
                
                //If this not a valid email skip this email
                if(str_contains($user->user_email,'.empty')){
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

            // pause 1 second to prevent signal overload
            sleep(1);
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