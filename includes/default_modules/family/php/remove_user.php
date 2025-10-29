<?php
namespace SIM\FAMILY;
use SIM;

//Remove user page and user marker on user account deletion
add_action('delete_user', __NAMESPACE__.'\userDeleted');
function userDeleted($userId){
	$family 		= new Family();
	$family->removeUser($userId);
}