<?php 
namespace SIM\MAIL_POSTING;
use SIM;

//Update the post
//http://postieplugin.com/postie_post_before/
add_filter('postie_post_before', function($post, $headers) {	
	if($post != null){
		$user = get_userdata($post['post_author']);
		//Change the post status to pending for all users without the editor role
		if ( !in_array('editor',$user->roles) and strpos(strtolower($post['post_title']), 'exchange rate') === false){
			$post['post_status'] = 'pending';
		}
		
		//Get the subject and process shortcodes in it
		$subject = do_shortcode($post['post_title']);
		$subject = str_replace("[SIM-Nigeria]","",$subject);
		//If mail is forwarded, edit the subject
		$post['post_title'] = trim(str_replace("Fwd:","",$subject));
	
		//Set the category
		if ($headers['from']['mailbox'].'@'.$headers['from']['host'] == SIM\getModuleOption('mail_posting', 'finance_email')){
			echo "Setting the category";
			//Set the category to Finance
			$post['post_category'] = [get_cat_ID('Finance')];
		}
	}
	return $post;
}, 10, 2);

add_filter('postie_post_after', function($post){
	//Only send message if post is published
	if($post['post_status'] == 'publish' and function_exists('SIM\SIGNAL\sendPostNotification')){
		SIM\SIGNAL\sendPostNotification($post['ID']);
	}elseif(function_exists('SIM\FRONTEND_POSTING\sendPendingPostWarning')){
		SIM\FRONTEND_POSTING\sendPendingPostWarning(get_post($post['ID']), false);
	}
});