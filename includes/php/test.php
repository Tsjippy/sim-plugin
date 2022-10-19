<?php
namespace SIM;

//Shortcode for testing
add_shortcode("test",function ($atts){
    global $wpdb;

    $signal = new SIGNAL\Signal(
        SIGNAL\Signal::FORMAT_JSON // Format
    );

    if(!$signal->valid){
        return $signal->error->get_error_message();
    }

    if(isset($_GET['link'])){
        echo $signal->link();
    }

    if(isset($_GET['text'])){
        echo $signal->send('+2349045252526', $_GET['text']);
    }

    if(isset($_GET['group'])){
        echo $signal->listGroups();
    }

    if(isset($_GET['verify'])){
        echo $signal->getUserStatus('+2349045252526');
    }

    if(isset($_GET['list'])){
        echo $signal->listDevices();
    }

    
    //echo $signal->linkPhoneNumber();
});

// turn off incorrect error on localhost
add_filter( 'wp_mail_smtp_core_wp_mail_function_incorrect_location_notice', '__return_false' );

