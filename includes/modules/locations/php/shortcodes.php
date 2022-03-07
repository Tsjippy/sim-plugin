<?php
namespace SIM\LOCATIONS;
use SIM;

add_shortcode("markerdescription", 	function ($atts){
    $a = shortcode_atts( array(
        'userid' => '',
    ), $atts );
    
    $user_id = $a['userid'];
    
    if(is_numeric($user_id)){
        wp_enqueue_style('sim_locations_style');
        
        $privacy_preference = (array)get_user_meta( $user_id, 'privacy_preference', true );

        $description = "";			
        if (empty($privacy_preference['hide_profile_picture'])){
            $description .= SIM\USERMANAGEMENT\display_profile_picture($user_id,[80,80]);
        }
        
        //Add the post link to the marker content
        $url			 = SIM\USERPAGE\get_user_page_url($user_id);
        $description	.= "<a href='$url' style='display:block;' class='page_link'>More info</a><br>";
        
        return $description;
    }
} );