<?php
namespace SIM\TRELLO;
use SIM;

add_action( 'rest_api_init', function () {
	//Route for notification messages
	register_rest_route( 
		'sim/v1', 
		'/trello', 
		array(
			'methods' 				=> \WP_REST_Server::ALLMETHODS,
			'callback' 				=> __NAMESPACE__.'\trelloActions',
			'permission_callback' 	=> '__return_true'
		)
	);
} );

/**
 * Trello webhook actions
 */
function trelloActions( \WP_REST_Request $request ) {

	$data	= $request->get_params()['action'];
	$trello	= new Trello();
	
	//only do something when the action is addMemberToCard
	if($data['type'] == 'addMemberToCard'){
		$checklistName 		= 'Website Actions';
		$cardId 			= $data['data']['card']['id'];
		$websiteChecklist	= '';
		
		//Remove self from the card again
		$trello->removeCardMember($cardId, $trello->memberId);
		
		//get the checklists of this card
		$checklists = $trello->getCardChecklist($cardId);
		
		//loop over the checklists to find the one we use
		foreach($checklists as $checklist){
			if($checklist->name == $checklistName)	$websiteChecklist = $checklist;
		}
		
		//If the checklist does not exist, create it
		if(empty($websiteChecklist))	$websiteChecklist = $trello->createChecklist($cardId, $checklistName);
		
		//Get the description of the card
		$desc = $trello->getCardField($cardId, 'desc');
		
		//First split on new lines
		$userProps	= [];
		foreach(explode("\n", $desc) as $item){
			//then split on :
			$temp = explode(':', $item);
			if($temp[0] != '')	$userProps[trim(strtolower($temp[0]))] = trim($temp[1]);
		}
		
		//useraccount exists
		if(is_numeric($userProps['user_id'])){
			$userId = $userProps['user_id'];
			
		//create an user account
		}elseif(!empty($userProps['email address']) and !empty($userProps['first name']) and !empty($userProps['last name'])  and !empty($userProps['duration'])){
			SIM\printArray('Creating user account from trello',true);
			SIM\printArray($userProps,true);
			
			//Find the duration number an quantifier in the result
			$pattern = "/([0-9]+) (months?|years?)/i";
			preg_match($pattern, $userProps['duration'],$matches);
			
			//Duration is defined in years
			if (strpos($matches[2], 'year') !== false) {
				$duration = $matches[1] * 12;
			//Duration is defined in months
			}else{
				$duration = $matches[1];
			}

			//create an useraccount
			$userId = SIM\addUserAccount(ucfirst($userProps['first name']), ucfirst($userProps['last name']), $userProps['email address'], true, $duration);
			
			if(is_numeric($userId)){
				//send welcome e-mail
				wp_new_user_notification($userId, null, 'both');

				//Add a checklist item on the card
				$trello->changeChecklistOption($cardId, $websiteChecklist, 'Useraccount created');
				
				//Add a comment
				$trello->addComment($cardId,"Account created, user id is $userId");
				
				//Update the description of the card
				$url	= SITEURL."/update-personal-info/?userid=$userId";
				$trello->updateCard($cardId, 'desc', $desc."%0A <a href='$url'>user_id:$userId</a>");
			}
		}else{
			//no account yet and we cannot create one
			return;
		}
		
		$username = get_userdata($userId)->user_login;
		
		/* 		
			SAVE COVER IMAGE AS PROFILE PICTURE 
		*/
		//Get the cover image url
		$url = $trello->getCoverImage($cardId);
		//If there is a cover image
		if(!empty($url)){
			//And an image is not yet set
			if(!is_numeric(get_user_meta($userId, 'profile_picture', true))){
				//Get the extension
				$ext = pathinfo($url, PATHINFO_EXTENSION);
				
				//Save the picture
				$filepath 	= wp_upload_dir()['path']."/private/profile_pictures/$username.$ext";
				
				if(file_exists($filepath)) unlink($filepath);
				
				file_put_contents($filepath, file_get_contents($url));
				
				//Add to the library
				$postId = SIM\addToLibrary($filepath);
				
				//Save in the db
				update_user_meta($userId,'profile_picture', $postId);
				
				//Add a checklist item on the card
				$trello->changeChecklistOption($cardId, $websiteChecklist, 'Profile picture');
			}
		}
	}
}

// Make mailtracker rest api url publicy available
add_filter('sim_allowed_rest_api_urls', function($urls){
	$urls[]	= 'sim/v1/trello';

	return $urls;
});