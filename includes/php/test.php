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
        $lines      = preg_split('/([(\r)(\n)(,)(.)])/', $post->post_content, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
        $firstLine  = strtolower($lines[0]);

        if(
            str_contains($firstLine, 'hi ') || 
            str_contains($firstLine, 'dear ') ||
            str_contains($firstLine, 'good afternoon') || 
            str_contains($firstLine, 'good morning') || 
            str_contains($firstLine, 'good evening') || 
            str_contains($firstLine, 'hey ')
        ){
            echo "$firstLine<br>";
            $lineToRemove   = $lines[0].$lines[1];

            unset($lines[0], $lines[1]);
            $postContent    = trim(force_balance_tags(implode('', $lines)));
        }else{
            //echo "$firstLine<br>";
        }
    } 
});

// turn off incorrect error on localhost
add_filter( 'wp_mail_smtp_core_wp_mail_function_incorrect_location_notice', '__return_false' );

