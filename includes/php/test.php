<?php
namespace SIM;

//Shortcode for testing
add_shortcode("test",function ($atts){
	global $Modules;

	$missingPages	= [];
	foreach(get_users() as $user){
		$jobs=[];
		$userMinistries 	= (array)get_user_meta( $user->ID, "user_ministries", true);

		foreach($userMinistries as $key=>$ministry){
			if($key == "Other"){
				$jobs['other']	= $ministry;
			}else{
				$key	= str_replace('_', ' ', $key);
				$page	= get_page_by_title($key, 'OBJECT', 'location');
				if(empty($page)){
					if(!in_array($key, $missingPages)){
						$missingPages[]	= $key;
					}
				}else{
					$jobs[$page->ID]	= $ministry;
				}
			}
		}

		cleanUpNestedArray($jobs);

		update_user_meta( $user->ID, "jobs", $jobs);
	}

	printArray($missingPages);
});

// turn off incorrect error on localhost
add_filter( 'wp_mail_smtp_core_wp_mail_function_incorrect_location_notice', '__return_false' );