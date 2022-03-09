<?php
namespace SIM\FRONTEND_POSTING;
use SIM;

use function SIM\get_module_option;

add_action('init', function(){
	//add action for use in scheduled task
	add_action( 'expired_posts_check_action', 'SIM\FRONTEND_POSTING\expired_posts_check' );
	add_action( 'page_age_warning_action', 'SIM\FRONTEND_POSTING\page_age_warning' );
});

function schedule_tasks(){
    SIM\schedule_task('expired_posts_check_action', 'daily');

	$freq	= SIM\get_module_option('frontend_posting', 'page_age_reminder');
	if($freq){
		SIM\schedule_task('page_age_warning_action', $freq);
	}
}

function expired_posts_check(){
	//Get all posts with the expirydate meta key with a value equal or before today
	$posts = get_posts(array(
		'numberposts'      => -1,
		'meta_query' => array(
			'relation' => 'AND',
			array(
				'key' => 'expirydate',
				'compare' => 'EXISTS'
			),
			array(
				'key' => 'expirydate',
				'value' => date("Y-m-d"), 
				'compare' => '<=',
				'type' => 'DATE'
			),
		)
	));
	
	foreach($posts as $post){
		SIM\print_array("Moving '{$post->post_title}' to trash as it has expired");
		wp_trash_post($post->ID);
	}
}

//Function to check for old ministry pages
//Whoever checked the box for that ministry on their account gets an email to remind them about the update
function page_age_warning(){
	//Get all pages without the static content meta key
	$pages = get_posts(array(
		'numberposts'      => -1,
		'post_type'        => 'page',
		'meta_query' => array(
			array(
			 'key' => 'static_content',
			 'compare' => 'NOT EXISTS'
			),
		)
	));
	
	//Months converted to seconds (days*hours*minutes*seconds)
	$max_age_in_seconds		= SIM\get_module_option('frontend_posting', 'max_page_age') * 30.4375 *24 *60 * 60;
	
	//Loop over all the pages
	foreach ( $pages as $page ) {
		//Get the ID of the current page
		$post_id 				= $page->ID;
		$post_title 			= $page->post_title;
		//Get the last modified date
		$seconds_since_updated 	= time()-get_post_modified_time('U',true,$page);
		$page_age				= round($seconds_since_updated/60/60/24);

		//If it is X days since last modified
		if ($seconds_since_updated > $max_age_in_seconds){
			//Get the edit page url
			$url = add_query_arg( ['post_id' => $post_id], get_permalink( get_module_option('frontend_posting', 'publish_post_page') ) );
			
			//Send an e-mail
			$recipients = get_page_recipients($post_title);
			foreach($recipients as $recipient){
				//Only email if valid email
				if(strpos($recipient->user_email,'.empty') === false){
					$subject = "Please update the contents of ".$post_title;
					$message = 'Dear '.$recipient->display_name.',<br><br>';
					$message .= 'It has been '.$page_age.' days since the page with title "'.$post_title.'" on <a href="https://simnigeria.org">SIM Nigeria</a> has been updated.<br>';
					$message .= 'Please follow this link to update it: <a href="'.$url.'">the link</a>.<br><br>';
					$message .= 'Kind regards,<br><br>';
					$headers = array('Content-Type: text/html; charset=UTF-8');
				
					wp_mail( $recipient->user_email, $subject, $message, $headers );
				}
				
				//Send Signal message
				SIM\try_send_signal("Hi ".$recipient->first_name.",\nPlease update the page '$post_title' here:\n\n$url",$recipient->ID);
			}
		}
	}
}

//Function to check who the recipients should be for the page update mail
function get_page_recipients($page_title){
	$recipients = [];
	
	//Get all the users with a ministry set
	$users = get_users( 
		array( 
			'meta_key'     => 'user_ministries'
		)	
	);
	
	//Loop over the users to see if they have this ministry set
	foreach($users as $user){
		if (isset(get_user_meta( $user->ID, 'user_ministries', true)[$page_title])){
			$recipients[] = $user;
		}
	}
	
	//If no one is responsible for this page
	if(count($recipients) == 0){
		$recipients = get_users( array(
			'role'    => 'contentmanager',
		));
	}
	
	return $recipients;
}