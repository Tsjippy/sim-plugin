<?php
namespace SIM\USERMANAGEMENT;
use SIM;

//Add link to the user menu to resend the confirmation e-mail
add_filter( 'user_row_actions', function ( $actions, $user ) {
    $actions['Resend welcome mail'] = "<a href='".SITEURL."/wp-admin/users.php?send_activation_email=$user->ID'>Resend welcome email</a>";
    return $actions;
}, 10, 2 );

add_action('admin_menu', function() {
	//Process the request
    $user_id    = $_GET['send_activation_email'];
	if(is_numeric($user_id )){
		$email = get_userdata($user_id )->user_email;
		SIM\print_array("Sending welcome email to $email");
		wp_new_user_notification($user_id, null, 'user');
	}
});