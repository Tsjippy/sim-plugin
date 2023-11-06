<?php
namespace SIM;

use mikehaertl\shellcommand\Command;

//Shortcode for testing
add_shortcode("test",function ($atts){
    global $wpdb;

    $results = $wpdb->get_results(  "SELECT * FROM `{$wpdb->prefix}postmeta` WHERE `meta_key`='geo_latitude'"  );
    foreach($results as $result){
        echo " latitude:";
        echo get_post_meta($result->post_id, 'geo_latitude', true);

        echo "<br> longitude:";
        echo get_post_meta($result->post_id, 'geo_longitude', true);

        echo "<br> location:";
        $location   = get_post_meta($result->post_id, 'location', true);
        if(!empty($location) && !is_array($location)){
            print_r(json_decode($location, true));
        }elseif(!empty($location) && is_array($location)){
            update_post_meta($result->post_id, 'location', json_encode($location));
        }else{
            update_post_meta($result->post_id, 'location', json_encode([
                'address'   => get_post_meta($result->post_id, 'geo_address', true),
                'latitude'  => get_post_meta($result->post_id, 'geo_latitude', true),
                'longitude' => get_post_meta($result->post_id, 'geo_longitude', true)
            ]));
        }

        echo '<br><br>';

        delete_post_meta($result->post_id, 'geo_address');
        delete_post_meta($result->post_id, 'geo_latitude');
        delete_post_meta($result->post_id, 'geo_longitude');
    }

    $results = $wpdb->get_results(  "SELECT * FROM `{$wpdb->prefix}postmeta` WHERE `meta_key`='location'"  );
    foreach($results as $result){
        $location   = get_post_meta($result->post_id, 'location', true);
        if(!empty($location) && is_array($location)){
            cleanUpNestedArray($location);

            if(empty($location)){
                delete_post_meta($result->post_id, 'location');
            }else{
                update_post_meta($result->post_id, 'location', json_encode($location));
            }
        }
    }
    
    

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

