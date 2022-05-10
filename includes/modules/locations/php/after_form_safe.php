<?php
namespace SIM\LOCATIONS;
use SIM;

// Update marker icon when family picture is updated
add_action('sim_update_family_picture', function($userId, $attachmentId){
    $maps       = new Maps();
    $markerId 	= get_user_meta($userId,"marker_id",true);
    $url        = wp_get_attachment_url($attachmentId);
    $iconTitle	= get_userdata($userId)->last_name.' family';
    
    $maps->create_icon($markerId, $iconTitle, $url, 1);

    //Save the marker id for all family members
    $partner    = SIM\has_partner($userId);
	if(empty($markerId) and $partner)	$markerId = get_user_meta($partner ,"marker_id", true);
	SIM\update_family_meta( $userId, "marker_id", $markerId);
}, 10, 2);

// Update marker title whenever there are changes to the family
add_action('sim_family_safe', function($userId){
    $maps       = new Maps();

	//Update the marker title
	$markerId   = get_user_meta($userId,"marker_id",true);

    $family     = SIM\family_flat_array($userId);

    if(empty($family)){
        $title = get_userdata($userId)->display_name;
    }else{
        $title = get_userdata($userId)->last_name." family";
    }
    
	$maps->update_marker_title($markerId, $title);
});

//Update marker whenever the location changes
add_action('sim_location_update', function($userId, $location){
    global $wpdb;

    //Get any existing marker id from the db
    $markerId = get_user_meta($userId,"marker_id",true);
    if (is_numeric($markerId)){
        //Retrieve the marker icon id from the db
        $query = $wpdb->prepare("SELECT icon FROM {$wpdb->prefix}ums_markers WHERE id = %d ", $markerId);
        $markerIconId = $wpdb->get_var($query);
        
        //Set the marker_id to null if not found in the db
        if($markerIconId == null) $markerId = null;
    }

    $maps   = new Maps();
    //Marker does not exist, create it
    if (!is_numeric($markerId)){			
        //Create a marker
        $maps->create_marker($userId, $location);
    //Marker needs an update
    }else{
        $maps->update_marker_location($markerId, $location);
    }
}, 10, 2);

// Remove marker when location is removed
add_action('sim_location_removal', function($userId){
	//Delete the marker as well
    $maps   = new Maps();
	$maps->remove_personal_marker($userId);
    delete_user_meta( $userId, 'marker_id');
});


// Update marker icon when family picture is changed
add_filter('before_saving_formdata',function($formresults, $formname, $userId){
	if($formname != 'profile_picture') return $formresults;	
	
	$privacyPreference = (array)get_user_meta( $userId, 'privacy_preference', true );
	$family				= get_user_meta($userId, 'family', true);
    $maps               = new Maps();
	
	//update a marker icon only if privacy allows and no family picture is set
	if (empty($privacyPreference['hide_profile_picture']) and !is_numeric($family['picture'][0])){
		$markerId = get_user_meta($userId,"marker_id",true);
		
		//New profile picture is set, update the marker icon
		if(is_numeric(get_user_meta($userId,'profile_picture',true))){
			$icon_url = SIM\USERMANAGEMENT\get_profile_picture_url($userId);
			
			//Save profile picture as icon
			$maps->create_icon($markerId, get_userdata($userId)->user_login, $icon_url, 1);
		}else{
			//remove the icon
			$maps->remove_icon($markerId);
		}
	}

	return $formresults;
},10,3);