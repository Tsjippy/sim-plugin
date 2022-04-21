<?php
namespace SIM\USERPAGE;
use SIM;

add_action('delete_user', function ($user_id){
    $family = SIM\family_flat_array($user_id);
	//Only remove if there is no family
	if (count($family) == 0){
        //Check if a page exists for this person
        $missionary_page    = SIM\getUserPageId($user_id);
        if (is_numeric($missionary_page)){
            //page exists, delete it
            wp_delete_post($missionary_page);
            SIM\print_array("Deleted the missionary page $missionary_page");
        }
    }elseif(count($family) == 1){
        //Get the partners display name to use as the new title
        $title = get_userdata(array_values($family)[0])->display_name;

        //Update
        update_user_page_title($user_id, $title);
    }
});