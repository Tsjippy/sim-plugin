<?php
namespace SIM\EVENTS;
use SIM;

function get_anniversaries(){
	global $Events;

	$messages = [];

	$Events->retrieve_events(date('Y-m-d'),date('Y-m-d'));

	foreach($Events->events as $event){
		$start_year	= get_post_meta($event->ID,'celebrationdate',true);
		if(!empty($start_year)){
			$title	= $event->post_title;
			$age	= SIM\get_age_in_words($start_year);
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

add_filter('sim_after_bot_payer', function($args){
	$anniversary_messages = get_anniversaries();
	//If there are anniversaries
	if(count($anniversary_messages) > 0){
		$args['message'] .= "\n\nToday is the ";

		$message_string	= '';

		//Loop over the anniversary_messages
		foreach($anniversary_messages as $user_id=>$msg){
			if(!empty($message_string))$message_string .= " and the ";

			$userdata	= get_userdata($user_id);
			$couple_string	= $userdata->first_name.' & '.get_userdata(SIM\has_partner(($userdata->ID)))->display_name;

			$msg	= str_replace($couple_string,"of $couple_string",$msg);
			$msg	= str_replace($userdata->display_name, "of {$userdata->display_name}", $msg);

			$message_string .= $msg;
			$args['urls'] .= SIM\USERPAGE\get_user_page_url($user_id)."\n";
		}
		$args['message'] .= $message_string.'.';
	}

	return $args;
});

add_filter('sim_after_bot_payer', function($args){
	$arrival_users = SIM\get_arriving_users();
	//If there are arrivals
	if(count($arrival_users) >0){
		if(count($arrival_users)==1){
			$args['message'] .= "\n\n".$arrival_users[0]->display_name." arrives today.";
			$args['urls'] .= SIM\USERPAGE\get_user_page_url($arrival_users[0]->ID)."\n";
		}else{
			$args['message'] .= "\n\nToday the following people will arrive: ";
			//Loop over the arrival_users
			foreach($arrival_users as $user){
				$args['message'] .= $user->display_name."\n";
				$args['urls'] .= SIM\USERPAGE\get_user_page_url($user->ID)."\n";
			}
		}
	}

	return $args;
});

add_action('delete_user', function($user_id){
	global $Events;

	//Remove birthday events
	$birthday_post_id = get_user_meta($user_id,'birthday_event_id',true);
	if(is_numeric($birthday_post_id))	$Events->remove_db_rows($birthday_post_id);

	$anniversary_id	= get_user_meta($user_id,'SIM Nigeria anniversary_event_id',true);
	if(is_numeric($anniversary_id))	$Events->remove_db_rows($anniversary_id);
});

//Get birthdays
function birthday() {
	if (is_user_logged_in()){
		$html			= "";
		$current_user	= wp_get_current_user();
		
		if(SIM\get_module_option('events', 'enable')){
			$anniversary_messages = get_anniversaries();
			
			//If there are anniversaries
			if(count($anniversary_messages) >0){
				$html .= '<div name="anniversaries" style="text-align: center; font-size: 18px;">';
					$html .= '<h3>Celebrations:</h3>';
					$html .= '<p>';
						$html .= "Today is the ";

				//Loop over the anniversary_messages
				$message_string	= '';
				foreach($anniversary_messages as $user_id=>$message){
					if(!empty($message_string))$message_string .= " and the ";

					$couple_string	= $current_user->first_name.' & '.get_userdata(SIM\has_partner(($current_user->ID)))->display_name;

					if($user_id  == $current_user->ID){
						$message	= str_replace($couple_string,"of you and your spouse my dear ".$current_user->first_name."!<br>",$message);
						$message	= str_replace($current_user->display_name,"of you my dear ".$current_user->first_name."!<br>",$message);
					}else{
						$userdata	= get_userdata($user_id);
						//Get the url of the user page
						$url		= SIM\USERPAGE\get_user_page_url($user_id);
						$message	= str_replace($couple_string,"of <a href='$url'>$couple_string</a>",$message);
						$message	= str_replace($userdata->display_name,"of <a href='$url'>{$userdata->display_name}</a>",$message);
					}

					$message_string	.= $message;
				}
				$html .= $message_string;
				$html .= '.</p></div>';
			}
		}
		
		$arrival_users = SIM\get_arriving_users();
		//If there are arrivals
		if(count($arrival_users) >0){
			$html 	.= '<div name="arrivals" style="text-align: center; font-size: 18px;">';
			$html 	.= '<h3>Arrivals</h3>';
			
			if(count($arrival_users)==1){
				//Get the url of the user page
				$url	 = SIM\USERPAGE\get_user_page_url($arrival_users[0]->ID);
				$html	.= '<p><a href="'.$url.'">'.$arrival_users[0]->display_name."</a> arrives today!";
			}else{
				$html 	.= '<p>The following people arrive today:<br>';
				//Loop over the birthdays
				foreach($arrival_users as $user){
					$url 	 = SIM\USERPAGE\get_user_page_url($user->ID);
					$html 	.= '<a href="'.$url.'">'.$user->display_name."</a><br>";
				}
			}
			$html .= '.</p></div>';
		}
		
		return $html;
	}else{
		return "";	
	}
}