<?php
namespace SIM;

function get_missionary_page_id($user_id){
    return get_user_meta($user_id,"missionary_page_id",true);
}

//Create an missionary (family) page
function create_missionary_page($user_id){
	global $MissionariesPageID;
	
	//get the current page
	$page_id    = get_missionary_page_id($user_id);
	$userdata   = get_userdata($user_id);
	
    //return false when $user_id is not valid
    if(!$userdata) return false;

	//Check if this page exists and is published
	if(get_post_status ($page_id) != 'publish' ) $page_id = null;
	
	$family = family_flat_array($user_id);
	if (count($family)>0){
		$title = $userdata->last_name." family";
	}else{
		$title = $userdata->last_name.', '.$userdata->first_name;
	}
	
	$update = false;
	
	//Only create a page if the page does not exist
	if ($page_id == null){
		$update = true;

		// Create post object
		$missionary_page = array(
		  'post_title'    => $title,
		  'post_content'  => '',
		  'post_status'   => 'publish',
		  'post_type'	  => 'page',
		  'post_parent'   => $MissionariesPageID,
		);
		 
		// Insert the post into the database
		$page_id = wp_insert_post( $missionary_page );
		
		//Save missionary id as meta
		update_post_meta($page_id,'missionary_id',$user_id);
		
		print_array("Created missionary page with id $page_id");
	}else{
        $update = update_missionary_page_title($user_id, $title);
	}
	
	if($update == true and count($family)>0){
		//Check if family has other pages who should be deleted
		foreach($family as $family_member){
			//get the current page
			$member_page_id = get_user_meta($family_member,"missionary_page_id",true);
			
			//Check if this page exists and is already trashed
			if(get_post_status ($member_page_id) == 'trash' ) $member_page_id = null;
			
			//If there a page exists for this family member and its not the same page
			if($member_page_id != null and $member_page_id != $page_id){
				//Remove the current user page
				wp_delete_post($member_page_id);
				
				print_array("Removed missionary page with id $member_page_id");
			}
		}
	}
	
	//Add the post id to the user profile
	update_family_meta($user_id,"missionary_page_id",$page_id);
	
	//Return the id
	return $page_id;
}

function get_missionary_page_url($user_id){
	//Get the missionary page of this user
	$missionary_page_id = get_missionary_page_id($user_id);
	
	if(!is_numeric($missionary_page_id) or get_post_status($missionary_page_id ) != 'publish'){
        $missionary_page_id = create_missionary_page($user_id);

        if(!$missionary_page_id) return false;
    }

    $url = get_permalink($missionary_page_id);
    $url_without_https = str_replace('https://','',$url);
    
    //return the url
    return $url_without_https;
}

function get_missionary_page_link($user){
    if(is_numeric($user)){
        $user   = get_userdata($user);
        if(!$user) return false;
    }
    $url    = get_missionary_page_url($user->ID);
    if($url){
        $html   = "<a href='$url'>$user->display_name</a>";
    }else{
        $html   = $user->display_name;
    }

    return $html;
}

function update_missionary_page_title($user_id, $title){
    $page_id    = get_missionary_page_id($user_id);

    if(is_numeric($page_id)){
        $page = get_post($page_id);

        //update page title if needed
		if($page->post_title != $title){
            wp_update_post(
                array (
                    'ID'         => $page_id,
                    'post_title' => $title
                )
            );

            return true;
        }else{
            return false;
        }
    }else{
        return create_missionary_page($user_id);  
    } 
}

function remove_missionary_page($user_id){
    //Check if a page exists for this person
    $missionary_page    = get_missionary_page_id($user_id);
    if (is_numeric($missionary_page)){
        //page exists, delete it
        wp_delete_post($missionary_page);
        print_array("Deleted the missionary page $missionary_page");
    }
}