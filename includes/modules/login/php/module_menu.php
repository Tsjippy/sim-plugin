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

	<label>Page with the password reset form</label>
	<?php echo SIM\page_select("pw_reset_page", $settings["pw_reset_page"]); ?>
	<br>
	
	<label>Page with the registration form for new users</label>
	<?php echo SIM\page_select("register_page", $settings["register_page"]);?>
	<br>

	<label>Page with the two factor setup form</label>
	<?php echo SIM\page_select("2fa_page", $settings["2fa_page"]);?>
	<label>
		Anything you want to append to the url<br>
		<input type='text' name="2fa_page_extras" value="<?php echo $settings['2fa_page_extras'];?>">
	</label>
    
	<?php
}, 10, 3);