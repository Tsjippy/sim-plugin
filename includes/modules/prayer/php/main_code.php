<?php
namespace SIM\PRAYER;
use SIM;

//give prayer coordinator acces to prayer items		
add_filter('sim_frontend_content_edit_rights', function($edit_right, $post_category){
	// If we currently have no edit right
	if(!$edit_right){
		// If we have the prayer coordinator role and the post or page has the prayer category
		if(
			in_array('prayercoordinator', wp_get_current_user()->roles) and 
			(
				in_array(get_cat_ID('Prayer'), $post_category) or 
				in_array('prayer', $post_category)
			)
		){
			$edit_right = true;
		}
	}

	return $edit_right;
}, 10, 2);

//Get Prayerrequest
function prayer_request($plaintext = false) {
	if (is_user_logged_in()){
		//Get all the post belonging to the prayer category
		$prayer_posts = get_posts(array('category' => get_cat_ID('Prayer') ));
		
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
				$generic_start	= "\s*\([A-Za-z]\)\s*[\W]\s*";
				#$generic_start	= "\s*\([A-Za-z]\)\s*";
				$re_start	= $day_num.$generic_start;
				$re_next	= ($day_num+1).$generic_start;
				//look for the start of a prayer line, get everything after "30(T) – " until you find a B* or the next "30(T) – " or the end of the document
				$re			= "/(*UTF8)$re_start(.+?)((B\*)|$re_next|$)/m";
				preg_match_all($re, strip_tags($content), $matches, PREG_SET_ORDER, 0);
				
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