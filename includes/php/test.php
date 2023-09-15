<?php
namespace SIM;

use mikehaertl\shellcommand\Command;

//Shortcode for testing
add_shortcode("test",function ($atts){
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

