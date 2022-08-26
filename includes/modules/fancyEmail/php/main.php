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
add_action( 'wp_mail_failed', function( $wpError ) {
    if(!isset($wpError->errors['wp_mail_failed'][0]) || $wpError->errors['wp_mail_failed'][0] != 'You must provide at least one recipient email address.'){
        SIM\printArray($wpError);
    }
});

add_action('clean_up_email_messages_action', function(){
    $fancyEmail = new FancyEmail();
    $fancyEmail->cleanUpEmailMessages('');
});