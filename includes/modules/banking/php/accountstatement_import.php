<?php
namespace SIM\BANKING;
use SIM;

add_filter('postie_post_before', function($post) {	
	//Check if account statement mail
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
		$accountId = trim($matches[0][1]);
		
		//Change the user to the adminaccount otherwise get_users will not work
		wp_set_current_user(1);
		
		//Get all users with this financial_account_id meta key
		$users = get_users(
			array(
				'meta_query' => array(
					array(
						'key'		=> 'financial_account_id',
						'value'		=> $accountId,
						'compare'	=> 'LIKE'
					)
				)
			)
		);
		
		//Find the attachment url
		$attachments = get_attached_media("", $post['ID']);
		
		if($users != null and $attachments != null){
			//Make sure we only continue with an adult
			$loginName	= '';
			foreach($users as $user){
				if (!SIM\isChild($user->ID)){
					$loginName = $user->data->user_login;
					break;
				}
			}
			if(empty($loginName)) return false;
			
			//Loop over all attachments
			foreach($attachments as $attachment){
				$fileName = $attachment->post_name;
				
				//If this attachment is the account statement
				if (strpos($fileName, 'account-statement') !== false) {
					$fileLocation = get_attached_file($attachment->ID);
					
					//Read the contents of the attachment					
					$rtf = file_get_contents($fileLocation); 

					//Regex to find the month it applies to
					$re = '/.*Date Range.*([0-9]{2}-[a-zA-Z]*-[0-9]{4}).*/';
					//execute regex
					preg_match_all($re, $rtf, $matches, PREG_SET_ORDER, 0);
					
					//Create a date
					$postDate	= date_create($matches[0][1]);
					
					//Create a string based on the date
					$datestring	= date_format($postDate,"Y-m");
					
					$newLocation = str_replace("uploads/","uploads/private/account_statements/$loginName-$datestring-",$fileLocation);
					$wp_filesystem->move($fileLocation, $newLocation);
					wp_delete_attachment($attachment->ID, true);
					$newUrl = str_replace(wp_get_upload_dir()["basedir"],wp_get_upload_dir()["baseurl"], $newLocation);
				}
			}
			
			//If there is an account statment
			if(isset($newUrl)){				
				$year = date_format($postDate,"Y");
				foreach($users as $user){
					if (SIM\isChild($user->ID)) continue;
					
					//Get the account statement list
					$accountStatements = get_user_meta($user->ID, "account_statements", true);
					//create the array if it does not exist
					if(!is_array($accountStatements)) $accountStatements = [];
					
					//Create tge year array if it does not exist
					if(!isset($accountStatements[$year]) or (isset($accountStatements[$year]) and !is_array($accountStatements[$year]))) 	$accountStatements[$year] = [];
					
					//Add the new statement to the year array
					$accountStatements[$year][date_format($postDate,"F")] = str_replace(site_url(),'',$newUrl);
					
					//Update the list
					update_user_meta($user->ID, "account_statements", $accountStatements);
					
					// Get account page
					$accountUrl		= SIM\getValidPageLink(SIM\getModuleOption('user_management', 'account_page'));
					if($accountUrl){
						$message	= "See it here: \n\n$accountUrl";
					}else{
						$message	= '';
					}

					//Send signal message
					SIM\trySendSignal(
						"Hi $user->first_name,\n\nThe account statement for the month ".date_format($postDate,'F')." just got available on the website. $message\n\nDirect url to the statement:\n$newUrl",
						$user->ID
					);
				}
			}
			
			return null;
		}
	}else{
		return $post;
	}
});