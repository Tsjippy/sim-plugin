<?php
namespace SIM\SIGNAL;
use SIM;

// Send Signal message about the new or updated post
add_action('sim_after_post_save', function($post, $update){
    if(isset($_POST['signal']) and $_POST['signal'] == 'send_signal'){
        if($post->post_status == 'publish'){
            delete_post_meta($post->ID, 'signal');
            delete_post_meta($post->ID, 'signalmessagetype');

            //Send signal message
            send_post_notification($post);
        }else{
            update_post_meta($post->ID, 'signal','checked');
            update_post_meta($post->ID, 'signalmessagetype', $_POST['signalmessagetype']);
        }
    }    

    if($post->post_status == 'pending'){
        SIM\FRONTEND_POSTING\send_pending_post_warning($post, $update);
    }
}, 999, 2);