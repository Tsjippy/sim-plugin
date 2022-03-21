<?php 
namespace SIM\MAIL_POSTING;
use SIM;

//Update the post
//http://postieplugin.com/postie_post_before/
add_filter('postie_post_before', function($post, $headers) {	
	if($post != null){
		$user = get_userdata($post['post_author']);
		//Change the post status to pending for all users without the contentmanager role
		if ( !in_array('contentmanager',$user->roles) and strpos(strtolower($post['post_title']), 'exchange rate') === false){
			$post['post_status'] = 'pending';
		}
		
		//Get the subject and process shortcodes in it
		$subject = do_shortcode($post['post_title']);
		$subject = str_replace("[SIM-Nigeria]","",$subject);
		//If mail is forwarded, edit the subject
		$post['post_title'] = trim(str_replace("Fwd:","",$subject));
	
		//Set the category
		if ($headers['from']['mailbox'].'@'.$headers['from']['host'] == SIM\get_module_option('mail_posting', 'finance_email')){
			echo "Setting the category";
			//Set the category to Finance
			$post['post_category'] = [get_cat_ID('Finance')];
		}
	}
	return $post;
}, 10, 2);

add_filter('postie_post_after', function($post){
	//Only send message if post is published
	if($post['post_status'] == 'publish'){
		SIM\SIGNAL\send_post_notification($post['ID']);
	}else{
		SIM\FRONTEND_POSTING\send_pending_post_warning(get_post($post['ID']), false);
	}
});