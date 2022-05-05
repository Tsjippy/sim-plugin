<?php
namespace SIM\MAILCHIMP;
use SIM;
// Add to mailchimp on user creation
add_action( 'sim_after_user_approval', function($user_id){
	//Add to mailchimp
	$Mailchimp = new Mailchimp($user_id);
	$Mailchimp->add_to_mailchimp();
});
