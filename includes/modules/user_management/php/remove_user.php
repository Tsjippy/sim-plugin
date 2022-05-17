<?php
namespace SIM\USERMANAGEMENT;
use SIM;

//Remove user page and user marker on user account deletion
add_action('delete_user', function ($userId){
	$userdata		= get_userdata($userId);
	$displayname	= $userdata->display_name;
	
	SIM\printArray("Deleting userdata for user $displayname");
	
	$attachment_id = get_user_meta($userId,'profile_picture',true);
	if(is_numeric($attachment_id)){
		//Remove profile picture
		wp_delete_attachment($attachment_id,true);
		SIM\printArray("Removed profile picture for user $displayname");
	}

	$family = SIM\familyFlatArray($userId);
	//User has family
	if (count($family) > 0){		
		//Remove user from the family arrays of its relatives
		foreach($family as $relative){
			//get the relatives family array
			$relative_family = get_user_meta($relative,"family",true);
			if (is_array($relative_family)){
				//Find the familyrelation to $userId
				$result = array_search($userId, $relative_family);
				if($result){
					//Remove the relation
					unset($relative_family[$result]);
				}else{
					//Not found, check children
					if(is_array($relative_family['children'])){
						$children = $relative_family['children'];
						$result = array_search($userId, $children);
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