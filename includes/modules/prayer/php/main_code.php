<?php
namespace SIM\PRAYER;
use SIM;

//give prayer coordinator acces to prayer items
add_filter('sim_frontend_content_edit_rights', function($editRight, $postCategory){
	
	if(
		!$editRight														&&	// If we currently have no edit right
		in_array('prayercoordinator', wp_get_current_user()->roles)		&& 	// If we have the prayer coordinator role and the post or page has the prayer category
		(
			in_array(get_cat_ID('Prayer'), $postCategory) 				||
			in_array('prayer', $postCategory)
		)
	){
		$editRight = true;
	}

	return $editRight;
}, 10, 2);

/**
 *
 * Get the prayer request of today
 *
 * @param    string     $plainText      Whether we shuld return the prayer request in html or plain text
 * @param	bool		$verified		If we trust the request, default false
 *
 * @return   array|false     			An array containing the prayer request and pictures or false if no prayer request found
 *
**/
function prayerRequest($plainText = false, $verified=false) {
	if (!is_user_logged_in() && !$verified){
		return false;
	}

	//Get all the post belonging to the prayer category
	$posts = get_posts(
		array(
			'category'  		=> get_cat_ID('Prayer'),
			's'					=> date("F Y"),
			'numberposts'		=> -1,
			'search_columns'	=> ['post_title'],
			//'sentence'			=> true
		)
	);

	//Loop over them to find the post for this month
	foreach($posts as $post){
		// double check if the current month and year is in the title as the s parameter searches everywhere
		if(strpos($post->post_title, date("F")) === false && strpos($post->post_title, date("Y")) === false){
			continue;
		}

		//Content of page with all prayer requests of this month
		if($plainText){
			$content 	= wp_strip_all_tags($post->post_content);
			$content	= str_replace(["&nbsp;", '&amp;'], [' ','&'], $content);
		}else{
			$content	= $post->post_content;
		}
		
		if ($content != null){
			//Current date
			$datetime = date('Y-m-d');

			//Current day of the month
			$today 		= date('d-m-Y', strtotime($datetime));
			$tomorrow 	= date('d-m-Y', strtotime('+1 day', strtotime($datetime)));

			//Find the request of the current day, Remove the daynumber (dayletter) - from the request
			//space(A)space-space
			$genericStart	= "\s*\(\s*[A-Za-z]{1,2}\s*\)\s*[\W]\s*";
			$reStart		= "$today$genericStart";
			$reNext			= "$tomorrow$genericStart";

			//look for the start of a prayer line, get everything after "30(T) – " until you find a B* or the next "30(T) – " or the end of the document
			$re			= "/(*UTF8)$reStart(.+?)((B\*)|$reNext|$)/m";
			preg_match_all($re, strip_tags($content), $matches, PREG_SET_ORDER, 0);
			
			//prayer request found
			if (isset($matches[0][1]) && !empty($matches[0][1])){
				//Return the prayer request
				$prayer		= $matches[0][1];
				$urls		= [];
				$pictures	= [];

				// check if prayer contains a single name or a couples name
				$re		= "/(*UTF8)(?:[\w]+.?+(?:&|and).[A-Z][\w]+.[A-Z][\w]+)|(?:[A-Z][\w]+.?+){2,}/m";	
				preg_match_all($re, $prayer, $matches, PREG_SET_ORDER, 0);

				// found a name
				if(isset($matches[0][0])){
					$name	= end(explode('&', $matches[0][0]));

					$args= array(
						'search' => $name, // or login or nicename in this example
						'search_fields' => array('user_login','user_nicename','display_name')
					);

					$users = get_users($args);
					if(!empty($users[0])){
						// user page url
						$url		= SIM\maybeGetUserPageUrl($users[0]->ID);
						if($url){
							$urls[]	= $url;
						}

						// family picture
						$family			= get_user_meta($users[0]->ID, 'family', true);

						if(isset($family['picture'])){
							if(is_array($family['picture'])){
								$attachmentId	= $family['picture'][0];
							}elseif(is_numeric($family['picture'])){
								$attachmentId	= $family['picture'];
							}
							$pictures[] 	= get_attached_file($attachmentId);
						}else{
							$profilePicture	= get_user_meta($users[0]->ID, 'profile_picture', true);
							if(is_array($profilePicture) && isset($profilePicture[0])){
								$pictures[] = get_attached_file($profilePicture[0]);
							}elseif(is_numeric($profilePicture)){
								$pictures[] = get_attached_file($profilePicture);
							}
						}
					}
				}

				$params	= [];
				if($plainText){
					$params	= apply_filters('sim_after_bot_payer', ['message'=>$prayer, 'urls'=>$urls, 'pictures'=>$pictures]);
					$prayer	= $params['message']."\n\n".$params['urls'];
				}

				return ['prayer'=>$prayer, 'pictures'=>$params['pictures']];
			}
		}
	}


	if($plainText){
		return ['prayer'=>'Sorry I could not find any prayer request for today', 'pictures'=>[]];
	}
	return false;
}