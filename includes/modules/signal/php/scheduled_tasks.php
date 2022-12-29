<?php
namespace SIM\SIGNAL;
use SIM;


add_action('init', function(){
	//add action for use in scheduled task
	add_action( 'check_signal_action', function(){
        $signal = new Signal();
        $signal->checkPrerequisites();
    });

    // needed for async signal messages
    add_action( 'schedule_signal_message_action', __NAMESPACE__.'\sendSignalMessage', 10, 3);
});


function scheduleTasks(){
    SIM\scheduleTask('check_signal_action', 'daily');
}

/**
 * Remind people to add their signal message to the website
 */
function checkSignalNumbers(){
    // we can send a signal message directly from the server
	if(!SIM\getModuleOption(MODULE_SLUG, 'local')){
        return;
    }
    
    if(strpos(php_uname(), 'Linux') !== false){
        $signal = new SignalBus();
    }else{
        $signal = new Signal();
    }

    foreach(get_users() as $user){
        $phonenumber    = get_user_meta( $user->ID, 'signal_number', true );

        // check if valid signal number
        if(empty($phonenumber) || !$signal->isRegistered($phonenumber)){
            // remove the stored signal number
            delete_user_meta( $user->ID, 'signal_number');

            // loop over all phonenumbers to find the one connected with signal
            foreach(get_user_meta( $user->ID, 'phonenumbers', true ) as $phonenumber){
                // store if registered
                if($signal->isRegistered($phonenumber)){
                    update_user_meta( $user->ID, 'signal_number', $phonenumber );

                    // go to the next user
                    continue 2;
                }
            }

            // no signal number found, send reminder
            //If this not a valid email skip this email
            if(strpos($user->user_email,'.empty') !== false){
                continue;
            }

            $email    = new SignalEmail($user);
            $email->filterMail();
                
            $subject        = $email->subject;
            $message        = $email->message;
            $recipients	    = $user->user_email;

            SIM\printArray($subject, true);
            SIM\printArray($message, true);
            SIM\printArray($recipients, true);

            //wp_mail( $recipients, $subject, $message);
        }
    }
}
