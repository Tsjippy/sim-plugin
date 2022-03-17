<?php 
namespace SIM\MAIL_POSTING;
use SIM;

//Update the post
//http://postieplugin.com/postie_post_before/
add_filter('postie_post_before', function($post, $headers) {	
	//Check if account statement mail
	$post = check_if_account_statement($post);
	
	if($post != null){
		$user = get_userdata($post['post_author']);
		//Change the post status to pending for all users without the contentmanager role
		if ( !in_array('contentmanager',$user->roles)){
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

function check_if_account_statement($post){
	global $wp_filesystem;
	WP_Filesystem();
	
	//Get the content of the email
	$content = $post['post_content'];
	
	//regex query to find the accountid of exactly 6 digits
	$re = '/.*AccountID:.*-([0-9]{6})-.*/';
	//execute regex
	preg_match_all($re, $content, $matches, PREG_SET_ORDER, 0);
	
	//If there is a result, process it.
	if (count($matches[0]) > 1){
		//get the results
		$accountid = trim($matches[0][1]);
		
		//Change the user to the adminaccount otherwise get_users will not work
		wp_set_current_user(1);
		
		//Get all users with this financial_account_id meta key
		$users = get_users(
			array(
				'meta_query' => array(
					array(
						'key' => 'financial_account_id',
						'value' => $accountid,
						'compare' => 'LIKE'
					)
				)
			)
		);
		
		//Find the attachment url
		$attachments = get_attached_media("",$post['ID']);
		
		if($users != null and $attachments != null){
			//Make sure we only continue with an adult
			$login_name	= '';
			foreach($users as $user){
				if (!SIM\is_child($user->ID)){
					$login_name = $user->data->user_login;
					break;
				}
			}
			if(empty($login_name)) return false;
			
			//Loop over all attachments
			foreach($attachments as $attachment){
				$url = $attachment->guid;
				$file_name = $attachment->post_name;
				
				//If this attachment is the account statement
				if (strpos($file_name, 'account-statement') !== false) {
					$file_location = get_attached_file($attachment->ID);
					
					//Read the contents of the attachment					
					$rtf = file_get_contents($file_location); 

					//Regex to find the month it applies to
					$re = '/.*Date Range.*([0-9]{2}-[a-zA-Z]*-[0-9]{4}).*/';
					//execute regex
					preg_match_all($re, $rtf, $matches, PREG_SET_ORDER, 0);
					
					//Create a date
					$postdate = date_create($matches[0][1]);
					
					//Create a string based on the date
					$datestring = date_format($postdate,"Y-m");
					
					$new_location = str_replace("uploads/","uploads/private/account_statements/$login_name-$datestring-",$file_location);
					$wp_filesystem->move($file_location,$new_location);
					wp_delete_attachment($attachment->ID,true);
					$new_url = str_replace(wp_get_upload_dir()["basedir"],wp_get_upload_dir()["baseurl"],$new_location);
				}
			}
			
			//If there is an account statment
			if(isset($new_url)){				
				$year = date_format($postdate,"Y");
				foreach($users as $user){
					if (SIM\is_child($user->ID)) continue;
					
					//Get the account statement list
					$account_statements = get_user_meta($user->ID, "account_statements", true);
					//create the array if it does not exist
					if(!is_array($account_statements)) $account_statements = [];
					
					//Create tge year array if it does not exist
					if(!isset($account_statements[$year]) or (isset($account_statements[$year]) and !is_array($account_statements[$year]))) 	$account_statements[$year] = [];
					
					//Add the new statement to the year array
					$account_statements[$year][date_format($postdate,"F")] = str_replace(site_url(),'',$new_url);
					
					//Update the list
					update_user_meta($user->ID, "account_statements", $account_statements);
					
					//Send signal message
					SIM\try_send_signal(
						"Hi ".$user->first_name.",\n\nThe account statement for the month ".date_format($postdate,'F')." just got available on the website. See it here: \n\n".get_site_url(null, '/account/')."\n\nDirect url to the statement:\n$new_url",
						$user->ID
					);
				}
			}
			
			return null;
		}
	}else{
		return $post;
	}
}