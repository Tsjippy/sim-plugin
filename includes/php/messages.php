<?php
namespace SIM;

//Get Prayerrequest
function prayer_request($plaintext = false) {
	if (is_user_logged_in()){
		global $PrayerCategoryID;
		//Get all the post belonging to the prayer category
		$prayer_posts = get_posts(array('category' => $PrayerCategoryID ));
		
		//Loop over them to find the post for this month
		foreach($prayer_posts as $prayer_post){
			if (strpos($prayer_post->post_title, date("F")) !== false ) {
				$PrayerPageID = $prayer_post->ID;
			}
		}
		
		if (isset($PrayerPageID)){
			//Content of page with all prayer requests of this month
			if($plaintext){
				$content 	= wp_strip_all_tags(get_post($PrayerPageID)->post_content);
				$content	= str_replace(["&nbsp;", '&amp; '], [' ',''], $content);
			}else{
				$content	= get_post($PrayerPageID)->post_content;
			}
			
			if ($content != null){
				//Current date
				$datetime = date('Y-m-d'); 
				//Current day of the month
				$day_num = date('j', strtotime($datetime));

				//Find the request of the current day, Remove the daynumber (dayletter) - from the request
				//space(A)space-space
				$generic_start	= "\s*\([A-Za-z]\)\s*[^\w]\s*";
				$re_start	= $day_num.$generic_start;
				$re_next	= ($day_num+1).$generic_start;
				//look for the start of a prayer line, get everything after "30(T) – " until you find a B* or the next "30(T) – " or the end of the document
				$re			= "/(*UTF8)$re_start(.+?)((B\*)|$re_next|$)/m";
				preg_match_all($re, $content, $matches, PREG_SET_ORDER, 0);
				
				//No prayer request found
				if (sizeof($matches) == 0){
					return "";
				}else{
					//Return the prayer request
					return $matches[0][1];
				}
			}
		//No prayer request post for this month found
		}else{
			return "";
		}
	}else{
		return "";	
	}
}

//Get birthdays
function birthday() {
	global $Modules;

	if (is_user_logged_in()){
		$html			= "";
		$current_user	= wp_get_current_user();
		
		if(isset($Modules['celebrations']['enable'])){
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

					$couple_string	= $current_user->first_name.' & '.get_userdata(has_partner(($current_user->ID)))->display_name;

					if($user_id  == $current_user->ID){
						$message	= str_replace($couple_string,"of you and your spouse my dear ".$current_user->first_name."!<br>",$message);
						$message	= str_replace($current_user->display_name,"of you my dear ".$current_user->first_name."!<br>",$message);
					}else{
						$userdata	= get_userdata($user_id);
						//Get the url of the user page
						$url		= get_missionary_page_url($user_id);
						$message	= str_replace($couple_string,"of <a href='$url'>$couple_string</a>",$message);
						$message	= str_replace($userdata->display_name,"of <a href='$url'>{$userdata->display_name}</a>",$message);
					}

					$message_string	.= $message;
				}
				$html .= $message_string;
				$html .= '.</p></div>';
			}
		}
		
		$arrival_users = get_arriving_users();
		//If there are arrivals
		if(count($arrival_users) >0){
			$html 	.= '<div name="arrivals" style="text-align: center; font-size: 18px;">';
			$html 	.= '<h3>Arrivals</h3>';
			
			if(count($arrival_users)==1){
				//Get the url of the user page
				$url	 = get_missionary_page_url($arrival_users[0]->ID);
				$html	.= '<p><a href="'.$url.'">'.$arrival_users[0]->display_name."</a> arrives today!";
			}else{
				$html 	.= '<p>The following people arrive today:<br>';
				//Loop over the birthdays
				foreach($arrival_users as $user){
					$url 	 = get_missionary_page_url($user->ID);
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
