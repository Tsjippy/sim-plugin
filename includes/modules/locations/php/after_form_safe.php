<?php
namespace SIM\LOCATIONS;
use SIM;

// Update marker icon when family picture is updated
add_action('sim_update_family_picture', function($userId, $attachmentId){
    $maps       = new Maps();
    $markerId 	= get_user_meta($userId,"marker_id",true);
    $url        = wp_get_attachment_url($attachmentId);
    $iconTitle	= get_userdata($userId)->last_name.' family';
    
    $maps->createIcon($markerId, $iconTitle, $url, 1);

    //Save the marker id for all family members
    $partner    = SIM\hasPartner($userId);
	if(empty($markerId) && $partner){
        $markerId = get_user_meta($partner ,"marker_id", true);
    }

	SIM\updateFamilyMeta( $userId, "marker_id", $markerId);
}, 10, 2);

// Update marker title whenever there are changes to the family
add_action('sim_family_safe', function($userId){
    $maps       = new Maps();

	//Update the marker title
	$markerId   = get_user_meta($userId, "marker_id", true);

    $title      = SIM\findFamilyName($userId);
    
	$maps->updateMarkerTitle($markerId, $title);
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
        if($markerIconId == null){
            $markerId = null;
        }
    }

    $maps   = new Maps();
    //Marker does not exist, create it
    if (!is_numeric($markerId)){			
        //Create a marker
        $maps->createUserMarker($userId, $location);
    //Marker needs an update
    }else{
        $maps->updateMarkerLocation($markerId, $location);
    }
}, 10, 2);

// Remove marker when location is removed
add_action('sim_location_removal', function($userId){
	//Delete the marker as well
    $maps   = new Maps();
	$maps->removePersonalMarker($userId);
    delete_user_meta( $userId, 'marker_id');
});


// Update marker icon when family picture is changed
add_filter('sim_before_saving_formdata', function($formResults, $formName, $userId){
	if($formName != 'profile_picture'){
        return $formResults;
    }
	
	$privacyPreference = (array)get_user_meta( $userId, 'privacy_preference', true );
	$family				= (array)get_user_meta($userId, 'family', true);
    $maps               = new Maps();
	
	//update a marker icon only if privacy allows and no family picture is set
	if (empty($privacyPreference['hide_profile_picture']) && !is_numeric($family['picture'][0])){
		$markerId = get_user_meta($userId, "marker_id", true);
		
		//New profile picture is set, update the marker icon
		if(is_numeric(get_user_meta($userId, 'profile_picture', true))){
			$iconUrl = SIM\USERMANAGEMENT\getProfilePictureUrl($userId);
			
			//Save profile picture as icon
			$maps->createIcon($markerId, get_userdata($userId)->user_login, $iconUrl, 1);
		}else{
			//remove the icon
			$maps->removeIcon($markerId);
		}
	}

	return $formResults;
},10,3);


// Update marker  whenprivacy options are changed
add_filter('sim_before_saving_formdata', function($formResults, $formName, $userId){
	if($formName != 'user_generics'){
        return $formResults;	
    }

    if(is_array($formResults['privacy_preference']) && in_array("hide_location", $formResults['privacy_preference'])){
        $markerId = get_user_meta($userId, "marker_id", true);

        if(is_numeric($markerId)){
            $maps = new Maps();
            $maps->removeMarker($markerId);
        }
    }

	return $formResults;
},10,3);