<?php
namespace SIM\FANCYMAIL;
use SIM;

// Filter any wp_email
add_filter('wp_mail',function($args){
    $fancyEmail     = new FancyEmail();
    return $fancyEmail->filter_mail($args);
}, 10,1);


// show wp_mail() errors
add_action( 'wp_mail_failed', function( $wp_error ) {
    SIM\print_array($wp_error);
});