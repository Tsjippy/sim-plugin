<?php
namespace SIM;

use mikehaertl\shellcommand\Command;

//Shortcode for testing
add_shortcode("test",function ($atts){
    global $wpdb;
    
    $query  = "SELECT * FROM `{$wpdb->prefix}sim_events` INNER JOIN $wpdb->posts ON post_id = $wpdb->posts.ID WHERE $wpdb->posts.post_author NOT IN (SELECT ID FROM $wpdb->users)";

    printArray($wpdb->get_results($query), true);
});

// turn off incorrect error on localhost
add_filter( 'wp_mail_smtp_core_wp_mail_function_incorrect_location_notice', '__return_false' );

