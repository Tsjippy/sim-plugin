<?php
namespace SIM\BANKING;
use SIM;

//Remove user page and user marker on user account deletion
add_action('delete_user', function ($userId){
	$family = SIM\familyFlatArray($userId);
	//Only remove if there is no family
	if (count($family) == 0){		
		//Remove account statements
		$accountStatements = get_user_meta($userId, "account_statements", true);
		if(is_array($accountStatements)){
			foreach($accountStatements as $accountStatement){
				$filePath = str_replace(wp_get_upload_dir()["baseurl"], wp_get_upload_dir()["basedir"], $accountStatement);
				unlink($filePath);
			}
		}
    }
});