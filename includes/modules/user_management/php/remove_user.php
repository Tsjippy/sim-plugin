<?php
namespace SIM\USERMANAGEMENT;
use SIM;

//Remove user page and user marker on user account deletion
add_action('delete_user', function ($userId){
	$userdata		= get_userdata($userId);
	$displayname	= $userdata->display_name;
	
	SIM\printArray("Deleting userdata for user $displayname");
	
	$attachmentId = get_user_meta($userId, 'profile_picture', true);
	if(is_numeric($attachmentId)){
		//Remove profile picture
		wp_delete_attachment($attachmentId, true);
		SIM\printArray("Removed profile picture for user $displayname");
	}

	$family = SIM\familyFlatArray($userId);
	//User has family
	if (count($family) > 0){		
		//Remove user from the family arrays of its relatives
		foreach($family as $relative){
			//get the relatives family array
			$relativeFamily = get_user_meta($relative,"family",true);
			if (is_array($relativeFamily)){
				//Find the familyrelation to $userId
				$result = array_search($userId, $relativeFamily);
				if($result){
					//Remove the relation
					unset($relativeFamily[$result]);
				}else{
					//Not found, check children
					if(is_array($relativeFamily['children'])){
						$children	= $relativeFamily['children'];
						$result		= array_search($userId, $children);
						if($result!==null){
							//Remove the relation
							unset($children[$result]);
							//This was the only child, remove the whole children entry
							if (count($children)==0){
								unset($relativeFamily["children"]);
							}else{
								//update the family
								$relativeFamily['children'] = $children;
							}
						}
					}
				}
				if (count($relativeFamily)==0){
					//remove from db, there is no family anymore
					delete_user_meta($relative, "family");
				}else{
					//Store in db
					update_user_meta($relative, "family", $relativeFamily);					
				}
			}
		}
	}

	//Send e-mail
	$accountRemoveMail    = new AccountRemoveMail($userdata);
	$accountRemoveMail->filterMail();
						
	wp_mail( $userdata->user_email, $accountRemoveMail->subject, $accountRemoveMail->message);
});