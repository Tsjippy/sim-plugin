<?php
namespace SIM\LOCATIONS;
use SIM;

add_shortcode("markerdescription", 	function ($atts){
    $a = shortcode_atts( array(
        'userid' => '',
    ), $atts );
    
    $userId = $a['userid'];
    
    if(is_numeric($userId)){
        wp_enqueue_style('sim_locations_style');
        
        $privacy_preference = (array)get_user_meta( $userId, 'privacy_preference', true );

        $description = "";			
        if (empty($privacy_preference['hide_profile_picture'])){
            $description .= SIM\displayProfilePicture($userId, [80,80], true, true);
        }
        
        //Add the post link to the marker content
        $url			 = SIM\getUserPageUrl($userId);
        $description	.= "<a href='$url' style='display:block;' class='page_link'>More info</a><br>";
        
        return $description;
    }
} );

add_shortcode('ministry_description', function($atts){
    return get_location_employees($atts['name']);
});