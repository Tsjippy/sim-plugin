<?php
namespace SIM\FRONTENDPOSTING;
use SIM;

function sendPendingPostWarning($post, $update){	
	//Do not continue if already send
	if(!empty(get_post_meta($post->ID, 'pending_notification_send', true))){
		return;
	}
	
	//get all the content managers
	$users = get_users( array(
		'role'    => 'editor',
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
		//send signal message
		SIM\trySendSignal("$authorName just $actionText a $type. Please review it here:\n\n$url",$user->ID);

		$pendinfPostEmail    = new PendingPostEmail($user, $authorName, $actionText, $type, $url);
		$pendinfPostEmail->filterMail();
			
		//Send e-mail
		wp_mail( $user->user_email, $pendinfPostEmail->subject, $pendinfPostEmail->message);
	}
	
	//Mark warning as send
	update_metadata( 'post', $post->ID, 'pending_notification_send', true);
}

//Delete the indicator that the warning has been send
add_action(  'transition_post_status',  function ( $newStatus, $oldStatus, $post ) {
	if ($newStatus == 'publish' && $oldStatus == 'pending'){
		delete_post_meta($post->ID,'pending_notification_send');
	}
}, 10, 3 );

//Allow display attributes in post content
add_filter( 'safe_style_css', function( $styles ) {
    $styles[] = 'display';
    return $styles;
} );

//Add post edit button
add_action( 'generate_before_content', __NAMESPACE__.'\add_page_edit_button');
add_action( 'sim_before_content', __NAMESPACE__.'\add_page_edit_button');
function add_page_edit_button(){
	$content = get_the_content();
	
	//Only show if:
	if (
		is_user_logged_in() 							&&	// logged in and 
		strpos($content,'[front_end_post]') === false 	&&	// not already on the post edit page 
		is_singular() 									&& // it is a single page 
		!is_tax()											//not a archive page
	){
		global $post;
		
		$user 	= wp_get_current_user();
		$userId = $user->ID;

		//Get current users ministry and compound
		$userPageId 		= SIM\getUserPageId($userId);
		$ministries 		= get_user_meta($userId, "user_ministries", true);
		
		//This is a draft
		if(isset($_GET['p']) || isset($_GET['page_id'])){
			if(isset($_GET['p'])){
				$postId 		= $_GET['p'];
			}else{
				$postId 		= $_GET['page_id'];
			}
			
			$postTitle 		= get_the_title($postId);
			$postAuthor 		= get_the_author($postId);
		//published
		}else{
			$postId 			= get_the_ID();
			$postTitle 		= get_the_title();
			$postAuthor 		= get_the_author();
		}
		
		$postCategory 	= $post->post_category;
			
		//Add an edit page button if:
		if (
			$postAuthor == $user->display_name 													|| 	// Own page
			isset($ministries[str_replace(" ", "_", $postTitle)])								||	// ministry pafe 
			$userPageId == $postId																||	// pseronal user page
			apply_filters('sim_frontend_content_edit_rights', false, $postCategory)				||	// external filter
			in_array('editor', $user->roles)														// user is an editor
		){
			$type = $post->post_type;
			$buttonText = "Edit this $type";
			
			$url	= SIM\getValidPageLink(SIM\getModuleOption(MODULE_SLUG, 'front_end_post_pages')[0]);
			if(!$url){
				return;
			}
			$url = add_query_arg( ['post_id' => $postId], $url );
			echo "<a href='$url' class='button sim' id='pageedit'>$buttonText</a>";
		}
	}
}