<?php
namespace SIM\USERPAGE;
use SIM;

add_action('delete_user', function ($userId){
    $family = SIM\familyFlatArray($userId);

	//Only remove if there is no family
	if (empty($family)){
        //Check if a page exists for this person
        $userPage    = getUserPageId($userId);
        if (is_numeric($userPage)){
            //page exists, delete it
            wp_delete_post($userPage);
            SIM\printArray("Deleted the user page $userPage");
        }
    }elseif(count($family) == 1){
        //Get the partners display name to use as the new title
        $title = get_userdata(array_values($family)[0])->display_name;

        //Update
        updateUserPageTitle($userId, $title);
    }
});