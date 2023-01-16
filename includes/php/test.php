<?php
namespace SIM;

use mikehaertl\shellcommand\Command;

//Shortcode for testing
add_shortcode("test",function ($atts){
    global $wpdb;

    $users = get_users(array(
        'meta_key'     => 'signal_number',
        'meta_value'   => '+2349045252526' ,
    ));

    printArray($users, true);

});

// turn off incorrect error on localhost
add_filter( 'wp_mail_smtp_core_wp_mail_function_incorrect_location_notice', '__return_false' );

