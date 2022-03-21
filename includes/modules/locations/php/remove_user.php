<?php
namespace SIM\LOCATIONS;
use SIM;

add_action('delete_user', function ($user_id){
	global $Maps;

	$family = SIM\family_flat_array($user_id);
	//Only remove if there is no family
	if (count($family) == 0){
		//Check if a personal marker exists for this user
		$marker_id = get_user_meta($user_id,"marker_id",true);

        $Maps->remove_marker($marker_id);
	//User has family
	}elseif(count($family) == 1){
        //Get the partners display name to use as the new title
        $title = get_userdata(array_values($family)[0])->display_name ;

        //Update
        $marker_id = get_user_meta($user_id,"marker_id",true);
		$Maps->update_marker_title($marker_id, $title);
    }
});