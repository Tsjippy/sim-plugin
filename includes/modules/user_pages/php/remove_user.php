<?php
namespace SIM\USERPAGE;
use SIM;

add_action('delete_user', function ($userId){
    $family = SIM\familyFlatArray($userId);
	//Only remove if there is no family
	if (count($family) == 0){
        //Check if a page exists for this person
        $user_page    = SIM\getUserPageId($userId);
        if (is_numeric($user_page)){
            //page exists, delete it
            wp_delete_post($user_page);
            SIM\printArray("Deleted the user page $user_page");
        }
    }elseif(count($family) == 1){
        //Get the partners display name to use as the new title
        $title = get_userdata(array_values($family)[0])->display_name;

        //Update
        update_user_page_title($userId, $title);
    }
});