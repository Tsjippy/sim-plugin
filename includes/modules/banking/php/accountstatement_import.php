<?php
namespace SIM\BANKING;
use SIM;

add_filter('postie_post_before', __NAMESPACE__.'\postieBeforeFilter');
function postieBeforeFilter($post) {	
	//Check if account statement mail

	$accountStatement	= new AccountStatement($post);
		
	if($accountStatement->checkIfStatement()){
		return null;
	}
	
	return $post;
}

