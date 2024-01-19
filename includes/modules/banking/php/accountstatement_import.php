<?php
namespace SIM\BANKING;
use SIM;

add_filter('postie_post_before', __NAMESPACE__.'\postieBeforeFilter');
function postieBeforeFilter($post) {
	//Check if account statement mail

	$accountStatement	= new AccountStatement($post);
		
	if($accountStatement->checkIfStatement()){

		$csv	= '';

		foreach($accountStatement->statementNames as $file){
			if(strpos($file, '.csv')){
				$csv	= STATEMENT_FOLDER.$file;
				break;
			}
		}

		wp_mail($accountStatement->user->user_email, "CSV for {$post->post_title}", "Hi {$accountStatement->user->display_name},<br><br>Your account statement is processed, find it attached to this e-mail or on the website.", '', [$csv]);

		return null;
	}
	
	return $post;
}

