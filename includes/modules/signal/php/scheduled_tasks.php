<?php
namespace SIM\SIGNAL;
use SIM;


add_action('init', function(){
	//add action for use in scheduled task
	add_action( 'check_signal_action', function(){
        $signal = new Signal();
        $signal->checkPrerequisites();
    });

    add_action( 'schedule_signal_message_action', __NAMESPACE__.'\sendSignalMessage', 10, 3);
});


function scheduleTasks(){
    SIM\scheduleTask('check_signal_action', 'daily');
}

