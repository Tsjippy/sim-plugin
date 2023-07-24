<?php
namespace SIM\LOCATIONS;
use SIM;

add_action('delete_user', function ($userId){
	$maps	= new Maps();
	$family = SIM\familyFlatArray($userId);
	//Only remove if there is no family
	if (empty($family)){
		//Check if a personal marker exists for this user
		$markerId = get_user_meta($userId, "marker_id", true);

        $maps->removeMarker($markerId);
	//User has family
	}elseif(count($family) == 1){
        //Get the partners display name to use as the new title
        $title = get_userdata(array_values($family)[0])->display_name ;

        //Update
        $markerId = get_user_meta($userId, "marker_id", true);
		$maps->updateMarkerTitle($markerId, $title);
    }
});