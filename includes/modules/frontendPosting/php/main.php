<?php
namespace SIM\FRONTENDPOSTING;
use SIM;

function sendPendingPostWarning( object $post, $update){
	//Do not continue if already send
	if(!empty(get_post_meta($post->ID, 'pending_notification_send', true))){
		return;
	}

	$channels	= SIM\getModuleOption(MODULE_SLUG, 'pending-channels', false);

	if(empty($channels)){
		return;
	}
	
	$roles	= SIM\getModuleOption(MODULE_SLUG, 'content-manager-roles');
	
	//get all the content managers
	$users = get_users( array(
		'role__in'    => $roles,
	));
	
	if($update){
		$actionText = 'updated';
	}else{
		$actionText = 'created';
	}
	
	$type = $post->post_type;
	
	//send notification to all content managers
	$url			= SIM\ADMIN\getDefaultPageLink(MODULE_SLUG, 'front_end_post_pages');
	if(!$url){
		return;
	}

	$url			= add_query_arg( ['post_id' => $post->ID], $url );
	$authorName		= get_userdata($post->post_author)->display_name;
	
	foreach($users as $user){
		if(in_array('signal', $channels)){
			//send signal message
			SIM\trySendSignal("$authorName just $actionText a $type. Please review it here:\n\n$url", $user->ID, true);
		}

		if(in_array('email', $channels)){
			$pendinfPostEmail    = new PendingPostEmail($user, $authorName, $actionText, $type, $url);
			$pendinfPostEmail->filterMail();
				
			//Send e-mail
			wp_mail( $user->user_email, $pendinfPostEmail->subject, $pendinfPostEmail->message);
		}
	}
	
	//Mark warning as send
	update_metadata( 'post', $post->ID, 'pending_notification_send', true);
}

//Delete the indicator that the warning has been send
add_action(  'transition_post_status',  function ( $newStatus, $oldStatus, $post ) {
	if ($newStatus == 'publish' && $oldStatus == 'pending'){
		delete_post_meta($post->ID, 'pending_notification_send');
	}
}, 10, 3 );

//Allow display attributes in post content
add_filter( 'safe_style_css', function( $styles ) {
    $styles[] = 'display';
    return $styles;
} );

/**
 * Checks if the current user is allowed to edit a post
 *
 * @return	boolean			True if allowed
 */
function allowedToEdit($post){
	if(is_numeric($post)){
		$post	= get_post($post);
	}

	$postId			= $post->ID;
	$user 			= wp_get_current_user();
	$postAuthor 	= $post->post_author;
	$postCategory 	= $post->post_category;
	$userPageId 	= SIM\maybeGetUserPageId($user->ID);
	$ministries 	= (array)get_user_meta($user->ID, "jobs", true);

	if (
		$postAuthor == $user->ID 															|| 	// Own page
		in_array($post->ID, array_keys($ministries))										||	// ministry pafe
		$userPageId == $postId																||	// pseronal user page
		apply_filters('sim_frontend_content_edit_rights', false, $postCategory)				||	// external filter
		$user->has_cap( 'edit_others_posts' )													// user has permission to edit any post
	){
		return true;
	}

	return false;
}

//Add post edit button
add_filter( 'the_content', function ( $content ) {
	//Do not show if:
	if (
		!is_user_logged_in() 							||	// not logged in or
		strpos($content, '[front_end_post]') !== false 	||	// already on the post edit page
		!is_singular() 									||  // it is not a single page
		is_tax()										||	// not an archive page
		is_front_page()										// is the front page
	){
		return $content;
	}

	global $post;
	
	//This is a draft
	if(isset($_GET['p']) || isset($_GET['page_id'])){
		if(isset($_GET['p'])){
			$postId 	= $_GET['p'];
		}else{
			$postId 	= $_GET['page_id'];
		}
	//published
	}else{
		$postId 		= get_the_ID();
	}

	$blockedRoles	= get_post_meta($postId, 'excluded_roles', true);
	if(is_array($blockedRoles)){
		$roles 		= get_userdata(get_current_user_id())->roles;
		if(array_intersect($blockedRoles, $roles)){
			return '<div class="error">You have no permission to see this</div>';
		}
	}
		
	//Add an edit page button if:
	if ( allowedToEdit($postId) ){
		$type = $post->post_type;
		$buttonText = "Edit this $type";

		return "<button class='button small hidden' id='page-edit' data-id='$postId'>$buttonText</button><div class='content-wrapper'>$content</div>";
	}

	return $content;
}, 15);