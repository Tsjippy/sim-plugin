<?php
namespace SIM\SIGNAL;
use SIM;


add_action('init', function(){
	//add action for use in scheduled task
	add_action( 'check_signal_action', function(){
        $signal = new Signal();
        $signal->checkPrerequisites();
    });
});

function scheduleTasks(){
    SIM\scheduleTask('check_signal_action', 'daily');
}

