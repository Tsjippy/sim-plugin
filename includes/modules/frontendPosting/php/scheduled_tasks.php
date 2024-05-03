<?php
namespace SIM\FRONTENDPOSTING;
use SIM;

add_action('init', function(){
	//add action for use in scheduled task
	add_action( 'expired_posts_check_action', __NAMESPACE__.'\expiredPostsCheck' );
	add_action( 'page_age_warning_action', __NAMESPACE__.'\pageAgeWarning' );
	add_action( 'publish_posts_action', __NAMESPACE__.'\publishPost' );
	
});

function scheduleTasks(){
    SIM\scheduleTask('expired_posts_check_action', 'daily');

	$freq	= SIM\getModuleOption(MODULE_SLUG, 'page_age_reminder');
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

	$emails					= [];
	
	//Loop over all the pages
	foreach ( getOldPages() as $page ) {
		//Get the ID of the current page
		$postId 				= $page->ID;

		$postTitle 				= $page->post_title;

		//Get the edit page url
		$url		= SIM\ADMIN\getDefaultPageLink(MODULE_SLUG, 'front_end_post_pages');
		$url 		= add_query_arg( ['post_id' => $postId], $url );

		//Get the last modified date
		$secondsSinceUpdated 	= time() - get_post_modified_time('U', true, $page);
		$pageAge				= round($secondsSinceUpdated /60 /60 /24);
		
		//Send an e-mail
		$recipients = getPageRecipients($page);
		foreach($recipients as $recipient){
			$email	= $recipient->user_email;
			//Only email if valid email
			if(!str_contains($email,'.empty')){

				if(!isset($emails[$email])){

					$postOutOfDateEmail    = new PostOutOfDateEmail($recipient, $postTitle, $pageAge, $url);
					$postOutOfDateEmail->filterMail();

					$emails[$email]	= [
						'subject'	=> $postOutOfDateEmail->subject,
						'message'	=> $postOutOfDateEmail->message,
						'count'		=> 1
					];
				}else{
					$postOutOfDateEmail    = new PostOutOfDateEmails($recipient, $postTitle, $pageAge, $url);
					$postOutOfDateEmail->filterMail();

					// replace the main message
					if($emails[$email]['count'] == 1){
						$emails[$email]['message']	= $postOutOfDateEmail->message;
					}
					$message	= $emails[$email]['message'];

					$count		= intval($emails[$email]['count']);
					$count++;

					$emails[$email]	= [
						'subject'	=> $postOutOfDateEmail->subject,
						'message'	=> "$message<br><a href='$url'>$postTitle</a><br>",
						'count'		=> $count
					];
				}
			}
		}
	}

	foreach($emails as $address=>$email){
		wp_mail( $address, $email['subject'], $email['message']);
	}
}

/**
 * Function to check who the recipients should be for the page update mail
 */
function getPageRecipients($page){

	$recipients = [];
	
	//Get all the users with a ministry set
	$users = get_users( 
		array( 
			'meta_key'     => 'jobs'
		)	
	);
	
	//Loop over the users to see if they have this ministry set
	foreach($users as $user){
		if (in_array($page->ID, array_keys(get_user_meta( $user->ID, 'jobs', true)))){
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