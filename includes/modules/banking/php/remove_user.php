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
});