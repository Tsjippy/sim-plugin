<?php
namespace SIM;

use mikehaertl\shellcommand\Command;

//Shortcode for testing
add_shortcode("test",function ($atts){
    global $wpdb;

    global $Modules;

    echo 'test';


    $posts = get_posts(
		array(
			'post_type'		=> 'any',
			//'author'		=> 137,
			'numberposts'	=> -1,
		)
	);

    foreach($posts as $post){
       
    } 
});

// turn off incorrect error on localhost
add_filter( 'wp_mail_smtp_core_wp_mail_function_incorrect_location_notice', '__return_false' );

