<?php
namespace SIM\MAILCHIMP;
use SIM;
// Add to mailchimp on user creation
add_action( 'sim_after_user_approval', function($userId){
	//Add to mailchimp
	$Mailchimp = new Mailchimp($userId);
	$Mailchimp->add_to_mailchimp();
});
