<?php
namespace SIM\FRONTEND_POSTING;
use SIM;

function send_pending_post_warning($post, $update){	
	//Do not continue if already send
	if(get_post_meta($post->ID,'pending_notification_send',true) != '') return;
	
	//get all the content managers
	$users = get_users( array(
		'role'    => 'editor',
	));
	
	if($update){
		$action_text = 'updated';
	}else{
		$action_text = 'created';
	}
	
	$type = $post->post_type;
	
	//send notification to all content managers
	$url			= SIM\getValidPageLink(SIM\get_module_option('frontend_posting', 'publish_post_page'));
	if(!$url)	 return;
	$url			= add_query_arg( ['post_id' => $post->ID], $url );
	$author_name	= get_userdata($post->post_author)->display_name;
	
	foreach($users as $user){
		//send signal message
		SIM\try_send_signal("$author_name just $action_text a $type. Please review it here:\n\n$url",$user->ID);

		$pendinfPostEmail    = new PendingPostEmail($user, $author_name, $action_text, $type, $url);
		$pendinfPostEmail->filterMail();
			
		//Send e-mail
		wp_mail( $user->user_email, $pendinfPostEmail->subject, $pendinfPostEmail->message);
	}
	
	//Mark warning as send
	update_post_meta($post->ID,'pending_notification_send',true);
}

//Delete the indicator that the warning has been send
add_action(  'transition_post_status',  function ( $new_status, $old_status, $post ) {
	if ($new_status == 'publish' and $old_status == 'pending'){
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
	
	//Only show if logged in and not already on the post edit page and it is a single page, not a archive page
	if (is_user_logged_in() and strpos($content,'[front_end_post]') === false and is_singular() and !is_tax()){
		global $post;
		
		$user = wp_get_current_user();
		$user_id = $user->ID;

		//Get current users ministry and compound
		$missionary_page_id = SIM\getUserPageId($user_id);
		$user_ministries 	= get_user_meta($user_id, "user_ministries", true);
		$user_compound 		= get_user_meta($user_id, "location", true);
		if(!is_array($user_compound)) $user_compound = [];
		
		//This is a draft
		if(isset($_GET['p']) or isset($_GET['page_id'])){
			if(isset($_GET['p'])){
				$post_id 		= $_GET['p'];
			}else{
				$post_id 		= $_GET['page_id'];
			}
			
			$post_title 		= get_the_title($post_id);
			$post_author 		= get_the_author($post_id);
		//published
		}else{
			$post_id 			= get_the_ID();
			$post_title 		= get_the_title();
			$post_author 		= get_the_author();
		}
		
		if(isset($user_compound['compound'])){
			$user_compound = $user_compound['compound'];
		}
		
		$post_category 	= $post->post_category;
			
		//Add an edit page button if this page a page describing a ministry this person is working for or a comound an user lives on, or the personal page
		if (
			$post_author == $user->display_name 												or 
			isset($user_ministries[str_replace(" ","_",$post_title)])							or 
			$missionary_page_id == $post_id														or
			apply_filters('sim_frontend_content_edit_rights', false, $post_category) == true	or
			in_array('editor',$user->roles)
		){
			$type = $post->post_type;
			$button_text = "Edit this $type";
			
			$url	= SIM\getValidPageLink(SIM\get_module_option('frontend_posting', 'publish_post_page'));
			if(!$url) return;
			$url = add_query_arg( ['post_id' => $post_id], $url );
			echo "<a href='$url' class='button sim' id='pageedit'>$button_text</a>";
		}
	}
}