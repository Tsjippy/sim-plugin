<?php
namespace SIM\USERMANAGEMENT;
use SIM;

//Remove user page and user marker on user account deletion
add_action('delete_user', function ($user_id){
	$userdata		= get_userdata($user_id);
	$displayname	= $userdata->display_name;
	
	SIM\print_array("Deleting userdata for user $displayname");
	
	$attachment_id = get_user_meta($user_id,'profile_picture',true);
	if(is_numeric($attachment_id)){
		//Remove profile picture
		wp_delete_attachment($attachment_id,true);
		SIM\print_array("Removed profile picture for user $displayname");
	}

	$family = SIM\family_flat_array($user_id);
	//User has family
	if (count($family) > 0){		
		//Remove user from the family arrays of its relatives
		foreach($family as $relative){
			//get the relatives family array
			$relative_family = get_user_meta($relative,"family",true);
			if (is_array($relative_family)){
				//Find the familyrelation to $user_id
				$result = array_search($user_id, $relative_family);
				if($result){
					//Remove the relation
					unset($relative_family[$result]);
				}else{
					//Not found, check children
					if(is_array($relative_family['children'])){
						$children = $relative_family['children'];
						$result = array_search($user_id, $children);
						if($result!==null){
							//Remove the relation
							unset($children[$result]);
							//This was the only child, remove the whole children entry
							if (count($children)==0){
								unset($relative_family["children"]);
							}else{
								//update the family
								$relative_family['children'] = $children;
							}
						}
					}
				}
				if (count($relative_family)==0){
					//remove from db, there is no family anymore
					delete_user_meta($relative,"family");
				}else{
					//Store in db
					update_user_meta($relative,"family",$relative_family);					
				}
			}
		}
	}

	//Send e-mail
	$accountRemoveMail    = new AccountRemoveMail($userdata);
	$accountRemoveMail->filterMail();
						
	wp_mail( $userdata->user_email, $accountRemoveMail->subject, $accountRemoveMail->message);
});