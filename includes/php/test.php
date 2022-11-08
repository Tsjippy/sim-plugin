<?php
namespace SIM;

use mikehaertl\shellcommand\Command;

//Shortcode for testing
add_shortcode("test",function ($atts){
    global $wpdb;

    $ids=[];

    $result	= $wpdb->get_results('SELECT * FROM wp_sim_form_submissions WHERE form_id=8 and archived=0');

    foreach($result as $r){
        $results=unserialize($r->formresults);
        if(!isset($results['travel'])){
            $ids[]  = $results['id'];
        }
    }
    printArray($ids, true);
});

// turn off incorrect error on localhost
add_filter( 'wp_mail_smtp_core_wp_mail_function_incorrect_location_notice', '__return_false' );