<?php
namespace SIM\MAILCHIMP;
use SIM;

// Add to mailchimp on user creation
add_action( 'sim_approved_user', function($userId){
	//Add to mailchimp
	$Mailchimp = new Mailchimp($userId);
	$Mailchimp->addToMailchimp();
});
