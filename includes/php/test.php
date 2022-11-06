<?php
namespace SIM;

use mikehaertl\shellcommand\Command;

//Shortcode for testing
add_shortcode("test",function ($atts){
    global $wpdb;

    $result	= $wpdb->get_results('SELECT * FROM wp_sim_form_submissions WHERE form_id=8 and archived=0');

    foreach($result as $r){
        printArray(unserialize($r->formresults)['id'], true);
    }
});

// turn off incorrect error on localhost
add_filter( 'wp_mail_smtp_core_wp_mail_function_incorrect_location_notice', '__return_false' );