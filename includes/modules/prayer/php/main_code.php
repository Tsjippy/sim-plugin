<?php
namespace SIM\PRAYER;
use SIM;

//give prayer coordinator acces to prayer items		
add_filter('sim_frontend_content_edit_rights', function($editRight, $postCategory){
	// If we currently have no edit right
	if(!$editRight){
		// If we have the prayer coordinator role and the post or page has the prayer category
		if(
			in_array('prayercoordinator', wp_get_current_user()->roles) and 
			(
				in_array(get_cat_ID('Prayer'), $postCategory) or 
				in_array('prayer', $postCategory)
			)
		){
			$editRight = true;
		}
	}

	return $editRight;
}, 10, 2);

/**
 *
 * Get the prayer request of today
 *
 * @param    string     $plainText      Whether we shuld return the prayer request in html or plain text
 * @return   string|false     			The prayer request or false if no prayer request found
 *
**/
function prayerRequest($plainText = false) {
	if (is_user_logged_in()){
		//Get all the post belonging to the prayer category
		$posts = get_posts(
			array(
				'category'  => get_cat_ID('Prayer'),
				's'			=> date("F Y")
			)
		);

		$prayer	= '';
		
		//Loop over them to find the post for this month
		foreach($posts as $post){
			// double check if the current month is in the title as the s parameter searches everywhere
			if(strpos($post->post_title, date("F Y")) === false){
				continue;
			}

			//Content of page with all prayer requests of this month
			if($plainText){
				$content 	= wp_strip_all_tags($post->post_content);
				$content	= str_replace(["&nbsp;", '&amp; '], [' ',''], $content);
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
				#$generic_start	= "\s*\([A-Za-z]\)\s*";
				$reStart	= $dayNum.$genericStart;
				$reNext	= ($dayNum+1).$genericStart;
				//look for the start of a prayer line, get everything after "30(T) – " until you find a B* or the next "30(T) – " or the end of the document
				$re			= "/(*UTF8)$reStart(.+?)((B\*)|$reNext|$)/m";
				preg_match_all($re, strip_tags($content), $matches, PREG_SET_ORDER, 0);
				
				//No prayer request found
				if (isset($matches[0][1]) && !empty($matches[0][1])){
					//Return the prayer request
					$prayer = $matches[0][1];
				}
			}
		}

		if(empty($prayer)){
			return false;
		}else{
			return $prayer;
		}
	}else{
		return false;	
	}
}