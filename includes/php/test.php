<?php
namespace SIM;

use mikehaertl\shellcommand\Command;

//Shortcode for testing
add_shortcode("test",function ($atts){
    global $wpdb;

    $query		= "SELECT * FROM `$wpdb->postmeta` WHERE `meta_key`='eventdetails'";
	$results	= $wpdb->get_results($query);

	foreach($results as $result){
		$details	= maybe_unserialize($result->meta_value);
		if(!is_array($details)){
			$details	= json_decode($details, true);
		}else{
			update_post_meta($result->post_id, 'eventdetails', json_encode($details));
		}

        if(!empty($details['repeated']) && !isset($details['isrepeated'])){
            $details['isrepeated']  = true;
            unset($details['repeated']);
            update_post_meta($result->post_id, 'eventdetails', json_encode($details));

            echo $details['organizer'].'<br>';
        }
    }

    //EVENTS\addRepeatedEvents();

});

// turn off incorrect error on localhost
add_filter( 'wp_mail_smtp_core_wp_mail_function_incorrect_location_notice', '__return_false' );

