<?php
namespace SIM;

//Shortcode for testing
add_shortcode("test",function ($atts){
    global $wpdb;

    $signal = new SIGNAL\SignalWrapperNew();

    if(!$signal->valid){
        return $signal->error->get_error_message();
    }

    echo $signal->linkPhoneNumber();

    return $signal->sendText(21, 'dgd');
});

// turn off incorrect error on localhost
add_filter( 'wp_mail_smtp_core_wp_mail_function_incorrect_location_notice', '__return_false' );

