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
			'category'  	=> get_cat_ID('Prayer'),
			's'				=> date("F Y"),
			'numberposts'	=> -1
		)
	);

	//Loop over them to find the post for this month
	foreach($posts as $post){
		// double check if the current month is in the title as the s parameter searches everywhere
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
			$dayNum = date('j', strtotime($datetime));

			//Find the request of the current day, Remove the daynumber (dayletter) - from the request
			//space(A)space-space
			$genericStart	= "\s*\([A-Za-z]\)\s*[\W]\s*";
			$reStart		= $dayNum.$genericStart;
			$reNext			= ($dayNum + 1).$genericStart;

			//look for the start of a prayer line, get everything after "30(T) – " until you find a B* or the next "30(T) – " or the end of the document
			$re			= "/(*UTF8)$reStart(.+?)((B\*)|$reNext|$)/m";
			preg_match_all($re, strip_tags($content), $matches, PREG_SET_ORDER, 0);
			
			//prayer request found
			if (isset($matches[0][1]) && !empty($matches[0][1])){
				//Return the prayer request
				$prayer	= $matches[0][1];

				$params	= [];
				if($plainText){
					$params	= apply_filters('sim_after_bot_payer', ['message'=>$prayer, 'urls'=>'', 'pictures'=>[]]);
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