<?php
namespace SIM;

use mikehaertl\shellcommand\Command;

//Shortcode for testing
add_shortcode("test",function ($atts){
    global $wpdb;

    $signal = new SIGNAL\SignalBus();
	$result = $signal->send('+234904525252', 'Failing message');

    if(strpos($result, 'Unregistered user') !== false){
        return 'user not registered';
    }
});

// turn off incorrect error on localhost
add_filter( 'wp_mail_smtp_core_wp_mail_function_incorrect_location_notice', '__return_false' );

