<?php
namespace SIM;

use mikehaertl\shellcommand\Command;

//Shortcode for testing
add_shortcode("test",function ($atts){
    global $wpdb;

    /* $signal = new SIGNAL\Signal();

    if(!$signal->valid){
        return '<div class="error">'.$signal->error->get_error_message().'</div>';
    } */

    require_once( __DIR__  . '/../modules/signal/lib/vendor/autoload.php');
});

// turn off incorrect error on localhost
add_filter( 'wp_mail_smtp_core_wp_mail_function_incorrect_location_notice', '__return_false' );