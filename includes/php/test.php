<?php
namespace SIM;

//Shortcode for testing
add_shortcode("test", function ($atts){
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    require_once ABSPATH . 'wp-admin/install-helper.php';

    global $wpdb;
    global $Modules;
    
    /*     $results    = $wpdb->get_results("SELECT * FROM `{$wpdb->prefix}sim_events` WHERE `enddate` = ''");
    foreach($results as $result){
        $meta   = json_decode(get_post_meta($result->post_id, 'eventdetails', true));

        foreach(['id', 'post_id', 'enddate', 'starttime', 'endtime', 'location', 'organizer', 'location_id', 'organizer_id', 'atendees', 'onlyfor'] as $column){
            if(isset($meta->$column)){
                $args[$column]	= $meta->$column;
            }
        }

        $wpdb->update("{$wpdb->prefix}sim_events",
            $args,
            array(
                'id'		=> $result->id
            ),
        );
    } */

    /* $posts = get_posts(
		array(
			'post_type'		=> 'any',
			//'author'		=> 137,
			'numberposts'	=> -1,
		)
	);

    foreach($posts as $post){
       
    }  */
});

// turn off incorrect error on localhost
add_filter( 'wp_mail_smtp_core_wp_mail_function_incorrect_location_notice', '__return_false' );