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
			'callback' 				=> __NAMESPACE__.'\trello_actions',
			'permission_callback' 	=> '__return_true'
		)
	);
} );

function trello_actions( \WP_REST_Request $request ) {

	$data	= $request->get_params()['action'];
	$trello	= new Trello();
	
	//only do something when the action is addMemberToCard
	if($data['type'] == 'addMemberToCard'){
		$checklist_name 	= 'Website Actions';
		$card_id 			= $data['data']['card']['id'];
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
		$user_props	= [];
		foreach(explode("\n", $desc) as $item){
			//then split on :
			$temp = explode(':', $item);
			if($temp[0] != '')	$user_props[trim(strtolower($temp[0]))] = trim($temp[1]);
		}
		
		//useraccount exists
		if(is_numeric($user_props['user_id'])){
			$userId = $user_props['user_id'];
			
		//create an user account
		}elseif(!empty($user_props['email address']) and !empty($user_props['first name']) and !empty($user_props['last name'])  and !empty($user_props['duration'])){
			SIM\printArray('Creating user account from trello',true);
			SIM\printArray($user_props,true);
			
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
			$userId = SIM\addUserAccount(ucfirst($user_props['first name']), ucfirst($user_props['last name']), $user_props['email address'], true, $duration);
			
			if(is_numeric($userId)){
				//send welcome e-mail
				wp_new_user_notification($userId,null,'both');

				//Add a checklist item on the card
				$trello->changeChecklistOption($card_id, $website_checklist, 'Useraccount created');
				
				//Add a comment
				$trello->addComment($card_id,"Account created, user id is $userId");
				
				//Update the description of the card
				$url	= SITEURL."/update-personal-info/?userid=$userId";
				$trello->updateCard($card_id, 'desc', $desc."%0A <a href='$url'>user_id:$userId</a>");
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
		$url = $trello->getCoverImage($card_id);
		//If there is a cover image
		if($url != ''){
			//And an image is not yet set
			if(!is_numeric(get_user_meta($userId,'profile_picture',true))){
				//Get the extension
				$ext = pathinfo($url, PATHINFO_EXTENSION);
				
				//Save the picture
				$filepath 	= wp_upload_dir()['path']."/private/profile_pictures/$username.$ext";
				
				if(file_exists($filepath)) unlink($filepath);
				
				file_put_contents($filepath, file_get_contents($url));
				
				//Add to the library
				$post_id = SIM\add_to_library($filepath);
				
				//Save in the db
				update_user_meta($userId,'profile_picture',$post_id);
				
				//Add a checklist item on the card
				$trello->changeChecklistOption($card_id, $website_checklist, 'Profile picture');
			}
		}
	}
}

// Make mailtracker rest api url publicy available
add_filter('sim_allowed_rest_api_urls', function($urls){
	$urls[]	= 'sim/v1/trello';

	return $urls;
});