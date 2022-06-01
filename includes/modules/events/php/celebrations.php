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

	$events = new Events();
	$events->retrieveEvents(date('Y-m-d'), date('Y-m-d'));

	foreach($events->events as $event){
		$startYear	= get_post_meta($event->ID,'celebrationdate',true);
		if(!empty($startYear) and $startYear != date('Y-m-d')){
			$title		= $event->post_title;
			$age		= SIM\getAge($startYear);
			$privacy	= (array)get_user_meta($event->post_author, 'privacy_preference', true);

			if(substr($title,0,8) == 'Birthday' and in_array('hide_age', $privacy)){
				$age	= '';
			}
			if(substr($title,0,3) != 'SIM'){
				$title	= lcfirst($title);
			}
			$messages[$event->post_author] = trim("$age $title");
		}
	}

	return $messages;
}

// Add anniversaries to signal bot message
add_filter('sim_after_bot_payer', function($args){
	$anniversaryMessages = getAnniversaries();
	//If there are anniversaries
	if(count($anniversaryMessages) > 0){
		$args['message'] .= "\n\nToday is the ";

		$messageString	= '';

		//Loop over the anniversary_messages
		foreach($anniversaryMessages as $userId=>$msg){
			if(!empty($messageString))$messageString .= " and the ";

			$userdata		= get_userdata($userId);
			$coupleString	= $userdata->first_name.' & '.get_userdata(SIM\hasPartner(($userdata->ID)))->display_name;

			$msg	= str_replace($coupleString,"of $coupleString",$msg);
			$msg	= str_replace($userdata->display_name, "of {$userdata->display_name}", $msg);

			$messageString .= $msg;
			$args['urls'] .= SIM\getUserPageUrl($userId)."\n";
		}
		$args['message'] .= $messageString.'.';
	}

	$arrivalUsers = getArrivingUsers();
	
	//If there are arrivals
	if(count($arrivalUsers) > 0){
		if(count($arrivalUsers)==1){
			$args['message'] 	.= "\n\n".$arrivalUsers[0]->display_name." arrives today.";
			$args['urls'] 		.= SIM\getUserPageUrl($arrivalUsers[0]->ID)."\n";
		}else{
			$args['message'] .= "\n\nToday the following people will arrive: ";
			//Loop over the arrival_users
			foreach($arrivalUsers as $user){
				$args['message'] 	.= $user->display_name."\n";
				$args['urls'] 		.= SIM\getUserPageUrl($user->ID)."\n";
			}
		}
	}

	return $args;
});

add_action('delete_user', function($userId){
	$events = new Events();

	//Remove birthday events
	$birthdayPostId = get_user_meta($userId,'birthday_event_id',true);
	if(is_numeric($birthdayPostId))	$events->removeDbRows($birthdayPostId);

	$anniversaryId	= get_user_meta($userId, SITENAME.' anniversary_event_id',true);
	if(is_numeric($anniversaryId))	$events->removeDbRows($anniversaryId);
});

/**
 *
 * Get the html birthday message and arriving users if any
 *
 * @return   string|false     Birthday and arrining usrs html or false if there are no events
 *
**/
function birthday() {
	if (is_user_logged_in()){
		$html				= "";
		$currentUser		= wp_get_current_user();
		$anniversaryMessages = getAnniversaries();
		
		//If there are anniversaries
		if(count($anniversaryMessages) >0){
			$html .= '<div name="anniversaries" style="text-align: center; font-size: 18px;">';
				$html .= '<h3>Celebrations:</h3>';
				$html .= '<p>';
					$html .= "Today is the ";

			//Loop over the anniversary_messages
			$messageString	= '';
			foreach($anniversaryMessages as $userId=>$message){
				if(!empty($messageString))$messageString .= " and the ";

				$coupleString	= $currentUser->first_name.' & '.get_userdata(SIM\hasPartner(($currentUser->ID)))->display_name;

				if($userId  == $currentUser->ID){
					$message	= str_replace($coupleString, "of you and your spouse my dear ".$currentUser->first_name."!<br>",$message);
					$message	= str_replace($currentUser->display_name, "of you my dear ".$currentUser->first_name."!<br>",$message);
				}else{
					$userdata	= get_userdata($userId);

					//Get the url of the user page
					$url		= SIM\getUserPageUrl($userId);
					$message	= str_replace($coupleString, "of <a href='$url'>$coupleString</a>", $message);
					$message	= str_replace($userdata->display_name, "of <a href='$url'>{$userdata->display_name}</a>", $message);
				}

				$messageString	.= $message;
			}
			$html .= $messageString;
			$html .= '.</p></div>';
		}
		
		$arrivingUsers = getArrivingUsers();
		//If there are arrivals
		if(count($arrivingUsers) >0){
			$html 	.= '<div name="arrivals" style="text-align: center; font-size: 18px;">';
			$html 	.= '<h3>Arrivals</h3>';
			
			if(count($arrivingUsers)==1){
				//Get the url of the user page
				$url	 = SIM\getUserPageUrl($arrivingUsers[0]->ID);
				$html	.= '<p><a href="'.$url.'">'.$arrivingUsers[0]->display_name."</a> arrives today!";
			}else{
				$html 	.= '<p>The following people arrive today:<br>';
				//Loop over the birthdays
				foreach($arrivingUsers as $user){
					$url 	 = SIM\getUserPageUrl($user->ID);
					$html 	.= '<a href="'.$url.'">'.$user->display_name."</a><br>";
				}
			}
			$html .= '.</p></div>';
		}
		
		return $html;
	}else{
		return false;	
	}
}