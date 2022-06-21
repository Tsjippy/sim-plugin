<?php
namespace SIM\FRONTEND_POSTING;
use SIM;

add_action('init', function(){
	//add action for use in scheduled task
	add_action( 'expired_posts_check_action', __NAMESPACE__.'\expiredPostsCheck' );
	add_action( 'page_age_warning_action', __NAMESPACE__.'\pageAgeWarning' );
	add_action( 'publish_posts_action', __NAMESPACE__.'\publishPost' );
	
});

function scheduleTasks(){
    SIM\scheduleTask('expired_posts_check_action', 'daily');

	$freq	= SIM\getModuleOption('frontend_posting', 'page_age_reminder');
	if($freq){
		SIM\scheduleTask('page_age_warning_action', $freq);
	}
}

/**
 * Checks for expired posts and removes them
 */
function expiredPostsCheck(){
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
		SIM\printArray("Moving '{$post->post_title}' to trash as it has expired");
		wp_trash_post($post->ID);
	}
}

/**
 * Checks for page who are not updated for a long time
*/
function pageAgeWarning(){
	//Change the user to the adminaccount otherwise get_users will not work
	wp_set_current_user(1);

	//Get all pages without the static content meta key
	$pages = get_posts(array(
		'numberposts'      => -1,
		'post_type'        => ['page', 'location'],
		'meta_query' => array(
			'relation' => 'AND',
			array(
				'key' 		=> 'static_content',
				'compare'	=> 'NOT EXISTS'
			),
			array(
				'key'		=> 'user_id',
				'compare'	=> 'NOT EXISTS'
			),
		)
	));
	
	//Months converted to seconds (days*hours*minutes*seconds)
	$maxAgeInSeconds		= SIM\getModuleOption('frontend_posting', 'max_page_age') * 30.4375 *24 *60 * 60;
	
	//Loop over all the pages
	foreach ( $pages as $page ) {
		//Get the ID of the current page
		$postId 				= $page->ID;
		$postTitle 			= $page->post_title;
		//Get the last modified date
		$secondsSinceUpdated 	= time()-get_post_modified_time('U',true,$page);
		$pageAge				= round($secondsSinceUpdated/60/60/24);

		//If it is X days since last modified
		if ($secondsSinceUpdated > $maxAgeInSeconds){
			//Get the edit page url
			$url		= SIM\getValidPageLink(SIM\getModuleOption('frontend_posting', 'publish_post_page'));
			$url 		= add_query_arg( ['post_id' => $postId], $url );		
			
			//Send an e-mail
			$recipients = getPageRecipients($postTitle);
			foreach($recipients as $recipient){
				//Only email if valid email
				if(strpos($recipient->user_email,'.empty') === false){
					$postOutOfDateEmail    = new PostOutOfDateEmail($recipient, $postTitle, $pageAge, $url);
					$postOutOfDateEmail->filterMail();
						
					wp_mail( $recipient->user_email, $postOutOfDateEmail->subject, $postOutOfDateEmail->message);
				}
			}
		}
	}
}

/**
 * Function to check who the recipients should be for the page update mail
 */
function getPageRecipients($page_title){

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
	if(empty(count($recipients))){
		$recipients = get_users( array(
			'role'    => 'editor',
		));
	}
	
	return $recipients;
}

// Remove scheduled tasks upon module deactivatio
add_action('sim_module_deactivated', function($moduleSlug){
	//module slug should be the same as grandparent folder name
	if($moduleSlug != MODULE_SLUG)	{return;}

	wp_clear_scheduled_hook( 'expired_posts_check_action' );
	wp_clear_scheduled_hook( 'page_age_warning_action' );
});