<?php
namespace SIM\EVENTS;
use SIM;

/**
 * Get all the users arriving today
 *
 * @return	array	The WP_Users arriving today
*/
function getArrivingUsers(){
	$date   = new \DateTime();
	return get_users(array(
		'meta_key'     => 'arrival_date',
		'meta_value'   => $date->format('Y-m-d'),
		'meta_compare' => '=',
	));
}

/**
 * Get all the anniversary events
 *
 * @return	array	all messages
*/
function getAnniversaries(){
	$messages = [];

	$events = new DisplayEvents();
	$events->retrieveEvents(date('Y-m-d'), date('Y-m-d'));

	foreach($events->events as $event){
		$startYear	= get_post_meta($event->ID, 'celebrationdate', true);
		if(!empty($startYear) && $startYear != date('Y-m-d')){
			$title		= $event->post_title;
			$age		= SIM\getAge($startYear);
			$privacy	= (array)get_user_meta($event->post_author, 'privacy_preference', true);

			if(substr($title, 0, 8) == 'Birthday' && in_array('hide_age', $privacy)){
				$age	= '';
			}
			if(substr($title, 0, 3) != 'SIM'){
				$title	= lcfirst($title);
			}

			//there happen to be more celeberations on the same day for one person
			if(isset($messages[$event->post_author])){
				$name	= get_userdata($event->post_author)->display_name;
				// remove the name of the previous text
				if(strpos($title, $name) !== false){
					$messages[$event->post_author]	= trim(str_replace($name, '', $messages[$event->post_author]));
				}
				$messages[$event->post_author]	.= ' and the ';
			}else{
				$messages[$event->post_author]	= '';
			}
			$messages[$event->post_author] .= trim("$age $title");
		}
	}

	return $messages;
}

// Add anniversaries to signal bot message
add_filter('sim_after_bot_payer', function($args){
	$anniversaryMessages = getAnniversaries();

	//If there are anniversaries
	if(!empty($anniversaryMessages)){
		$args['message'] .= "\n\nToday is the ";

		$messageString	= '';

		//Loop over the anniversary_messages
		foreach($anniversaryMessages as $userId=>$msg){
			$msg	= html_entity_decode($msg);
			if(!empty($messageString)){
				$messageString .= " and the ";
			}

			$userdata		= get_userdata($userId);
			$coupleString	= $userdata->first_name.' & '.get_userdata(SIM\hasPartner(($userdata->ID)))->display_name;

			$msg	= str_replace($coupleString, "of $coupleString", $msg);
			$msg	= str_replace($userdata->display_name, "of {$userdata->display_name}", $msg);

			$messageString .= $msg;
			$args['urls'] .= str_replace('https://', '', SIM\maybeGetUserPageUrl($userId))."\n";
		}
		$args['message'] .= $messageString.'.';
	}

	$arrivalUsers = getArrivingUsers();
	
	//If there are arrivals
	if(!empty($arrivalUsers)){
		if(count($arrivalUsers)==1){
			$args['message'] 	.= "\n\n".$arrivalUsers[0]->display_name." arrives today.";
			$args['urls'] 		.= str_replace('https://', '', SIM\maybeGetUserPageUrl($arrivalUsers[0]->ID))."\n";
		}else{
			$args['message'] .= "\n\nToday the following people will arrive: ";
			//Loop over the arrival_users
			foreach($arrivalUsers as $user){
				$args['message'] 	.= $user->display_name."\n";
				$args['urls'] 		.= str_replace('https://', '', SIM\maybeGetUserPageUrl($user->ID))."\n";
			}
		}
	}

	return $args;
});

add_action('delete_user', function($userId){
	$events = new CreateEvents();

	//Remove birthday events
	$birthdayPostId = get_user_meta($userId,'birthday_event_id',true);
	if(is_numeric($birthdayPostId)){
		$events->removeDbRows($birthdayPostId);
	}

	$anniversaryId	= get_user_meta($userId, SITENAME.' anniversary_event_id',true);
	if(is_numeric($anniversaryId)){
		$events->removeDbRows($anniversaryId);
	}
});

/**
 *
 * Adds html to the forntpage
 *
 * @return   string|false     Birthday and arrining usrs html or false if there are no events
 *
**/
add_filter('sim_frontpage_message', function($html){
	
	$html	.= anniversaryMessages();

	$html	.= arrivingUsersMessage();
	
	return $html;
});

/**
 *
 * Get the html birthday message
 *
 * @return   string|false     Anniversary html
 *
 */
function anniversaryMessages(){
	$currentUser			= wp_get_current_user();
	$anniversaryMessages 	= getAnniversaries();

	if(empty($anniversaryMessages)){
		return '';
	}

	$messageString	= '';

	//Loop over the anniversary_messages
	foreach($anniversaryMessages as $userId=>$message){
		if(!empty($messageString)){
			$messageString .= " and the ";
		}

		$partnerId		= SIM\hasPartner($userId);
		if(is_numeric($partnerId)){
			$partner		= get_userdata($partnerId);
		}
		$userdata	= get_userdata($userId);

		if($userId  == $currentUser->ID){
			$coupleLink	= "of you and your spouse my dear $currentUser->first_name!<br>";
			$link		= str_replace($currentUser->display_name, "of you my dear $currentUser->first_name!<br>", $message);
		}else{
			//Get the url of the user page
			$url		= SIM\maybeGetUserPageUrl($userId);
			$coupleLink	= "of <a href='$url'>{$userdata->first_name} & {$partner->display_name}</a>";
			$link		= "of <a href='$url'>{$userdata->display_name}</a>";
		}

		if(is_numeric($partnerId)){
			$pattern	= "/{$userdata->first_name}.*{$partner->display_name}/i";
			$message	= preg_replace($pattern, $coupleLink, $message);
		}
		$message	= str_replace($userdata->display_name, $link, $message);

		$messageString	.= $message;
	}

	$html = '<div name="anniversaries" style="text-align: center; font-size: 18px;">';
		$html .= '<h3>Celebrations:</h3>';
		$html .= '<p>';
			$html .= "Today is the $messageString";
		$html .= '.</p>';
	$html .= '</div>';

	return $html;
}

/**
 *
 * Get the arriving users if any
 *
 * @return   string     Arrining users html
 *
*/
function arrivingUsersMessage(){
	$arrivingUsers	= getArrivingUsers();
	$html			= '';

	//If there are arrivals
	if(!empty($arrivingUsers)){
		$html 	.= '<div name="arrivals" style="text-align: center; font-size: 18px;">';
			$html 	.= '<h3>Arrivals</h3>';

			$html .= '<p>';
		
				if(count($arrivingUsers)==1){
					//Get the url of the user page
					$url	 = SIM\maybeGetUserPageUrl($arrivingUsers[0]->ID);
					$html	.= '<a href="'.$url.'">'.$arrivingUsers[0]->display_name."</a> arrives today!";
				}else{
					$html 	.= 'The following people arrive today:<br>';
					//Loop over the arrivals
					foreach($arrivingUsers as $user){
						$url 	 = SIM\maybeGetUserPageUrl($user->ID);
						$html 	.= "<a href='$url'>$user->display_name</a><br>";
					}
				}
			$html .= '.</p>';
		$html .= '</div>';
	}

	return $html;
}
