<?php
namespace SIM\USERPAGE;
use SIM;

add_action('delete_user', function ($userId){
    $partner = SIM\hasPartner($userId, true);

	//Only remove if there is no family
	if (!$partner){
        //Check if a page exists for this person
        $userPage    = getUserPageId($userId);
        if (is_numeric($userPage)){
            //page exists, delete it
            wp_delete_post($userPage);
            SIM\printArray("Deleted the user page $userPage");
        }
    }else{
        //Get the partners display name to use as the new title
        $title = $partner->display_name;

        //Update
        updateUserPageTitle($partner->ID, $title);
    }
});