<?php
namespace SIM\MAILCHIMP;
use SIM;

// Add to mailchimp on user creation
add_action( 'user_register', function($userId){
	//Add to mailchimp
	$Mailchimp = new Mailchimp($userId);
	$Mailchimp->addToMailchimp();
});
