<?php
namespace SIM;

//Shortcode for testing
add_shortcode("test",function ($atts){
    $test   = maybe_unserialize(get_option('sidebars_widgets'));
    echo '';
});

// turn off incorrect error on localhost
add_filter( 'wp_mail_smtp_core_wp_mail_function_incorrect_location_notice', '__return_false' );

