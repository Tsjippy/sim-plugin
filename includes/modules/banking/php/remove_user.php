<?php
namespace SIM\BANKING;
use SIM;

//Remove user page and user marker on user account deletion
add_action('delete_user', function ($userId){
	$partner	= SIM\hasPartner($userId);

	//Only remove if there is no family
	if (!$partner){
		//Remove account statements
		$accountStatements = get_user_meta($userId, "account_statements", true);
		if(is_array($accountStatements)){
			foreach($accountStatements as $years){
				foreach($years as $accountStatement){
					$filePath = STATEMENT_FOLDER.$accountStatement;
					unlink($filePath);
				}
			}
		}
    }

	// banking is currently enabled
    $currentSetting = get_user_meta($userId, 'online_statements', true);
	if(is_array($currentSetting) && !empty(!$currentSetting)){
		$user		= get_user_by('ID', $userId);
		$email    	= new DisableBanking($user);
		$email->filterMail();
		
		wp_mail( $user->user_email, $email->subject, $email->message);
	}
});