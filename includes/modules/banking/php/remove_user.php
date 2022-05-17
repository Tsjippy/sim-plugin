<?php
namespace SIM\BANKING;
use SIM;

//Remove user page and user marker on user account deletion
add_action('delete_user', function ($userId){
	$family = SIM\familyFlatArray($userId);
	//Only remove if there is no family
	if (count($family) == 0){		
		//Remove account statements
		$account_statements = get_user_meta($userId, "account_statements", true);
		if(is_array($account_statements)){
			foreach($account_statements as $key => $account_statement){
				$file_path = str_replace(wp_get_upload_dir()["baseurl"],wp_get_upload_dir()["basedir"],$account_statement);
				unlink($file_path);
				SIM\printArray("Removed $file_path");
			}
		}
    }
});