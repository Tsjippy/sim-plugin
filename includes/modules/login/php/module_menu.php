<?php
namespace SIM\LOGIN;
use SIM;

const ModuleVersion		= '7.0.3';

add_action('sim_submenu_description', function($module_slug, $module_name){
	//module slug should be the same as grandparent folder name
	if($module_slug != basename(dirname(dirname(__FILE__))))	return;

	?>
	<p>
		This module turns on AJAX login and adds two factor login.<br>
		The normal login (wp-login.php) is no longer available once this module is activated.<br>
		A 'login' or 'logout' menu button will be added instead.<br>
		These buttons ae used for AJAX based logins and logouts, saving loading time.<br>
		<br>
		Three second factor options are available:<br>
		<ol>
			<li>Authenticator code</li>
			<li>E-mail</li>
			<li>Biometrics</li>
		</ol>
		As biometrics is device specific, it is advisable to also add e-mail or authenticator login.<br>
		<br>
		The login process will be this:<br>
		<ol>
			<li>Click login</li>
			<li>Fill in username and password</li>
			<li>Click verify credentials</li>
			<li>If a biometric login is enabled for the current device that will be used</li>
			<li>If no biometric login is enabled for the current device a authenticator or e-mail code will be requested</li>
			<li>If no second factor is setup yet the user will be redirected to the page to setup 2fa and cannot access the website.</li>
		</ol>
		<br>
		2fa setup is done on the page containging the twofa_setup shortcode.<br>
		Use like this: <code>[twofa_setup]</code>
	</p>
	<?php

},10,2);

add_action('sim_submenu_options', function($module_slug, $module_name, $settings){
	//module slug should be the same as grandparent folder name
	if($module_slug != basename(dirname(dirname(__FILE__))))	return;
	
	?><label>Page for logged in users</label>
	<?php echo SIM\page_select("home_page", $settings["home_page"]);?>
	<br>

	<label>Page with the two factor setup form</label>
	<?php echo SIM\page_select("2fa_page", $settings["2fa_page"]);?>
	<label>
		Anything you want to append to the url<br>
		<input type='text' name="2fa_page_extras" value="<?php echo $settings['2fa_page_extras'];?>">
	</label>

	<h4>E-mail with the two factor login code</h4>
	<label>Define the e-mail people get when they requested a code for login.</label>
	<?php
	$twoFAEmail    = new TwoFaEmail(wp_get_current_user());
	$twoFAEmail->printPlaceholders();
	$twoFAEmail->printInputs($settings);

	?>
	<br>
	<br>

	<h4>Warning e-mail for unsafe login</h4>
	<label>Define the e-mail people get when they login to the website and they have not configured two factor authentication.</label>
	<?php
	$unsafeLogin    = new UnsafeLogin(wp_get_current_user());
	$unsafeLogin->printPlaceholders();
	$unsafeLogin->printInputs($settings);

	?>
	<br>
	<br>
    
	<h4>Two factor login reset e-mail</h4>
	<label>Define the e-mail people get when their two factor login got reset by a user manager.</label>
	<?php
	$twoFaReset    = new TwoFaReset(wp_get_current_user());
	$twoFaReset->printPlaceholders();
	$twoFaReset->printInputs($settings);

	?>
	<br>
	<br>
	<h4>Two factor login confirmation e-mail</h4>
	<label>Define the e-mail people get when they have just enabled email verification.</label>
	<?php
	$emailVerfEnabled    = new EmailVerfEnabled(wp_get_current_user());
	$emailVerfEnabled->printPlaceholders();
	$emailVerfEnabled->printInputs($settings);

	?>
	<h4>E-mail to people who requested a password reset</h4>
	<label>Define the e-mail people get when they requested a password reset</label>
	<?php
	$passwordResetMail    = new PasswordResetMail(wp_get_current_user());
	$passwordResetMail->printPlaceholders();
	$passwordResetMail->printInputs($settings);
	?>
	<br>
	<br>

	<input type='hidden' name='password_reset_page' value='<?php echo SIM\get_module_option($module_slug, 'password_reset_page');?>'>
	<input type='hidden' name='register_page' value='<?php echo SIM\get_module_option($module_slug, 'register_page');?>'>
	<?php
}, 10, 3);

add_filter('sim_module_updated', function($options, $module_slug){
	//module slug should be the same as grandparent folder name
	if($module_slug != basename(dirname(dirname(__FILE__))))	return $options;

	$public_cat	= get_cat_ID('Public');

	// Create password reset page
	$page_id	= SIM\get_module_option($module_slug, 'password_reset_page');
	// Only create if it does not yet exist
	if(!$page_id or get_post_status($page_id) != 'publish'){
		$post = array(
			'post_type'		=> 'page',
			'post_title'    => 'Change password',
			'post_content'  => '[change_password]',
			'post_status'   => "publish",
			'post_author'   => '1',
			'post_category'	=> [$public_cat]
		);
		$page_id 	= wp_insert_post( $post, true, false);

		//Store page id in module options
		$options['password_reset_page']	= $page_id;
	}

	// Create register page
	$page_id	= SIM\get_module_option($module_slug, 'register_page');
	// Only create if it does not yet exist
	if(!$page_id or get_post_status($page_id) != 'publish'){
		$post = array(
			'post_type'		=> 'page',
			'post_title'    => 'Request user account',
			'post_content'  => '[request_account]',
			'post_status'   => "publish",
			'post_author'   => '1',
			'post_category'	=> [$public_cat]
		);
		$page_id 	= wp_insert_post( $post, true, false);

		//Store page id in module options
		$options['register_page']	= $page_id;
	}

	return $options;

}, 10, 2);

add_action('sim_module_deactivated', function($module_slug, $options){
	//module slug should be the same as grandparent folder name
	if($module_slug != basename(dirname(dirname(__FILE__))))	return;

	// Remove the auto created page
	wp_delete_post($options['password_reset_page'], true);
	wp_delete_post($options['register_page'], true);
}, 10, 2);