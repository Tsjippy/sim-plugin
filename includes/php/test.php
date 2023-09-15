<?php
namespace SIM;

use mikehaertl\shellcommand\Command;

//Shortcode for testing
add_shortcode("test",function ($atts){
    $signal = new SIGNAL\SignalBus();

    $signal->addToReceivedMessageLog('+2349045252526', 'message', '1694727807', '106,44,55,227,35,67,190,174,122,12,239,54,17,14,26,184,108,147,179,18,49,93,140,58,9,139,132,188,87,2,86,137');
    global $wpdb;


    /*     $posts = get_posts(
		array(
			'post_type'		=> 'any',
			'author'		=> 137,
			'numberposts'	=> -1,
		)
	);

    foreach($posts as $post){
        wp_update_post(
            [
                'ID'         	=> $post->ID,
				'post_author'	=> 292
            ], 
            false, 
            false
        );
    } */
});

// turn off incorrect error on localhost
add_filter( 'wp_mail_smtp_core_wp_mail_function_incorrect_location_notice', '__return_false' );

