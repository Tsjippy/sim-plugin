<?php
namespace SIM\TRELLO;
use SIM;

//Shortcode on the page which will be called by trello when something happens
add_shortcode("trello_webhook",function(){
	$trello	= new Trello();

	$data = json_decode(file_get_contents('php://input'));
	
	//only do something when the action is addMemberToCard
	if($data->action->type == 'addMemberToCard'){
		$checklist_name 	= 'Website Actions';
		$card_id 			= $data->action->data->card->id;
		$website_checklist	= '';
		
		//Remove self from the card again
		$trello->removeCardMember($card_id);
		
		//get the checklists of this card
		$checklists = $trello->getCardChecklist($card_id);
		
		//loop over the checklists to find the one we use
		foreach($checklists as $checklist){
			if($checklist->name == $checklist_name)	$website_checklist = $checklist;
		}
		
		//If the checklist does not exist, create it
		if($website_checklist == '')	$website_checklist = $trello->createChecklist($card_id, $checklist_name);
		
		//Get the description of the card
		$desc = $trello->getCardField($card_id, 'desc');
		
		//First split on new lines
		$data 		= explode("\n",$desc);
		$user_props	= [];
		foreach($data as $item){
			//then split on :
			$temp = explode(':', $item);
			if($temp[0] != '')	$user_props[trim(strtolower($temp[0]))] = trim($temp[1]);
		}
		
		//useraccount exists
		if(is_numeric($user_props['user_id'])){
			$user_id = $user_props['user_id'];
			
		//create an user account
		}elseif(!empty($user_props['email address']) and !empty($user_props['first name']) and !empty($user_props['last name'])  and !empty($user_props['duration'])){
			SIM\print_array('Creating user account from trello',true);
			SIM\print_array($user_props,true);
			
			//Find the duration number an quantifier in the result
			$pattern = "/([0-9]+) (months?|years?)/i";
			preg_match($pattern, $user_props['duration'],$matches);
			
			//Duration is defined in years
			if (strpos($matches[2], 'year') !== false) {
				$duration = $matches[1] * 12;
			//Duration is defined in months
			}else{
				$duration = $matches[1];
			}

			//create an useraccount
			$user_id = SIM\USERMANAGEMENT\add_user_account(ucfirst($user_props['first name']), ucfirst($user_props['last name']), $user_props['email address'], true, $duration);
			
			if(is_numeric($user_id)){
				//send welcome e-mail
				wp_new_user_notification($user_id,null,'both');

				//Add a checklist item on the card
				$trello->changeChecklistOption($card_id, $website_checklist, 'Useraccount created');
				
				//Add a comment
				$trello->addComment($card_id,"Account created, user id is $user_id");
				
				//Update the description of the card
				$url	= SITEURL."/update-personal-info/?userid=$user_id";
				$trello->updateCard($card_id, 'desc', $desc."%0A <a href='$url'>user_id:$user_id</a>");
			}
		}else{
			//no account yet and we cannot create one
			return;
		}
		
		$username = get_userdata($user_id)->user_login;
		
		/* 		
			SAVE COVER IMAGE AS PROFILE PICTURE 
		*/
		//Get the cover image url
		$url = $trello->getCoverImage($card_id);
		//If there is a cover image
		if($url != ''){
			//And an image is not yet set
			if(!is_numeric(get_user_meta($user_id,'profile_picture',true))){
				//Get the extension
				$ext = pathinfo($url, PATHINFO_EXTENSION);
				
				//Save the picture
				$filepath 	= wp_upload_dir()['path']."/private/profile_pictures/$username.$ext";
				
				if(file_exists($filepath)) unlink($filepath);
				
				file_put_contents($filepath, file_get_contents($url));
				
				//Add to the library
				$post_id = SIM\add_to_library($filepath);
				
				//Save in the db
				update_user_meta($user_id,'profile_picture',$post_id);
				
				//Add a checklist item on the card
				$trello->changeChecklistOption($card_id, $website_checklist, 'Profile picture');
			}
		}
	}
});