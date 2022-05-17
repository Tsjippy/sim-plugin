<?php
namespace SIM\EVENTS;
use SIM;

function get_arriving_users(){
	$date   = new \DateTime(); 
	$arrival_users = get_users(array(
		'meta_key'     => 'arrival_date',
		'meta_value'   => $date->format('Y-m-d'),
		'meta_compare' => '=',
	));
	
	return $arrival_users;
}

function get_anniversaries(){
	$messages = [];

	$events = new Events();
	$events->retrieve_events(date('Y-m-d'),date('Y-m-d'));

	foreach($events->events as $event){
		$start_year	= get_post_meta($event->ID,'celebrationdate',true);
		if(!empty($start_year) and $start_year != date('Y-m-d')){
			$title	= $event->post_title;
			$age	= SIM\getAge($start_year);
			$privacy= (array)get_user_meta($event->post_author, 'privacy_preference', true);

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

// Add anniversaries
add_filter('sim_after_bot_payer', function($args){
	$anniversary_messages = get_anniversaries();
	//If there are anniversaries
	if(count($anniversary_messages) > 0){
		$args['message'] .= "\n\nToday is the ";

		$message_string	= '';

		//Loop over the anniversary_messages
		foreach($anniversary_messages as $userId=>$msg){
			if(!empty($message_string))$message_string .= " and the ";

			$userdata	= get_userdata($userId);
			$couple_string	= $userdata->first_name.' & '.get_userdata(SIM\hasPartner(($userdata->ID)))->display_name;

			$msg	= str_replace($couple_string,"of $couple_string",$msg);
			$msg	= str_replace($userdata->display_name, "of {$userdata->display_name}", $msg);

			$message_string .= $msg;
			$args['urls'] .= SIM\getUserPageUrl($userId)."\n";
		}
		$args['message'] .= $message_string.'.';
	}

	$arrival_users = get_arriving_users();
	
	//If there are arrivals
	if(count($arrival_users) > 0){
		if(count($arrival_users)==1){
			$args['message'] .= "\n\n".$arrival_users[0]->display_name." arrives today.";
			$args['urls'] .= SIM\getUserPageUrl($arrival_users[0]->ID)."\n";
		}else{
			$args['message'] .= "\n\nToday the following people will arrive: ";
			//Loop over the arrival_users
			foreach($arrival_users as $user){
				$args['message'] .= $user->display_name."\n";
				$args['urls'] .= SIM\getUserPageUrl($user->ID)."\n";
			}
		}
	}

	return $args;
});

add_action('delete_user', function($userId){
	$events = new Events();

	//Remove birthday events
	$birthday_post_id = get_user_meta($userId,'birthday_event_id',true);
	if(is_numeric($birthday_post_id))	$events->remove_db_rows($birthday_post_id);

	$anniversary_id	= get_user_meta($userId, SITENAME.' anniversary_event_id',true);
	if(is_numeric($anniversary_id))	$events->remove_db_rows($anniversary_id);
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
		$anniversaryMessages = get_anniversaries();
		
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
		
		$arrivingUsers = get_arriving_users();
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