<?php
namespace SIM\FANCYEMAIL;
use SIM;

// Filter any wp_email
add_filter('wp_mail',function($args){
    $fancyEmail     = new FancyEmail();
    $args = $fancyEmail->filterMail($args);

    return $args;
}, 10, 1);


// skip mail if there are no recipients and mark as succesfull
add_filter( 'pre_wp_mail', function($shouldSkip, $atts ){
    if(empty($atts['to'])){
        return true;
    }
    return $shouldSkip;
}, 99, 2);

// show wp_mail() errors
add_action( 'wp_mail_failed', function( $wpError ) {
    if(!isset($wpError->errors['wp_mail_failed'][0]) || $wpError->errors['wp_mail_failed'][0] != 'You must provide at least one recipient email address.'){
        SIM\printArray($wpError);
    }
});

add_action( 'wp_mail_smtp_mailcatcher_send_failed', function($errorMessage, $instance, $mailMailer){
    SIM\printArray($errorMessage);
    SIM\printArray($instance);
    SIM\printArray($mailMailer);
}, 10, 3 );