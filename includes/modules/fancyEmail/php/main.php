<?php
namespace SIM\FANCYEMAIL;
use SIM;

// Filter any wp_email
add_filter('wp_mail',function($args){
    $fancyEmail     = new FancyEmail();
    $args = $fancyEmail->filterMail($args);

    return $args;
}, 10, 1);

// show wp_mail() errors
add_action( 'wp_mail_failed', function( $wp_error ) {
    SIM\printArray($wp_error);
});

add_action('clean_up_email_messages_action', function(){
    $fancyEmail = new FancyEmail();
    $fancyEmail->cleanUpEmailMessages('');
});