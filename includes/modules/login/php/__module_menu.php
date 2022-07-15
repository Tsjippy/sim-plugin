<?php
namespace SIM\LOGIN;
use SIM;

require( __DIR__  . '/../lib/vendor/autoload.php');

const MODULE_VERSION		= '7.0.14';
//module slug is the same as grandparent folder name
DEFINE(__NAMESPACE__.'\MODULE_SLUG', strtolower(basename(dirname(__DIR__))));

add_filter('sim_submenu_description', function($description, $moduleSlug){
	//module slug should be the same as the constant
	if($moduleSlug != MODULE_SLUG)	{
		return $description;
	}

	ob_start();
	$menus			= wp_get_nav_menus();
	$menuLocations	= get_nav_menu_locations();
	$menuActivated	= false;

	// loop over all menus
	foreach ( (array) $menus as $menu ) {
		// check if this menu has a location
		if ( array_search( $menu->term_id, $menuLocations ) ) {
			$menuActivated	= true;
		}
	}

	if(!$menuActivated){
		echo "<div class='error'>";
			echo "<br>You have no active menu, please activate one to have login and logout menu items<br><br>";
		echo "</div>";
	}

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
	$page1Id	= SIM\getModuleOption($moduleSlug, 'password_reset_page');
	$page2Id	= SIM\getModuleOption($moduleSlug, 'register_page');
	$page3Id	= SIM\getModuleOption($moduleSlug, '2fa_page');
	if(is_numeric($page1Id) || is_numeric($page2Id) || is_numeric($page3Id)){
		?>
		<p>
			<strong>Auto created pages:</strong><br>
			<a href='<?php echo get_permalink($page1Id);?>'>Change password</a><br>
			<a href='<?php echo get_permalink($page2Id);?>'>Request user account</a><br>
			<a href='<?php echo get_permalink($page3Id);?>'>Two Factor Authentication</a><br>
		</p>
		<?php
	}

	return ob_get_clean();
}, 10, 2);

add_filter('sim_submenu_options', function($optionsHtml, $moduleSlug, $settings){
	//module slug should be the same as grandparent folder name
	if($moduleSlug != MODULE_SLUG){
		return $optionsHtml;
	}

	ob_start();
	?>
	<p>
		You can enable user registration if you want.<br>
		If that's the case people can request an user account.<br>
		Once that account is approved they will be able to login.<br>
	</p>
	<label>
		<input type="checkbox" name="user_registration" value="enabled" <?php if($settings['user_registration']){echo 'checked';}?>>
		Enable user registration
	</label>

	<input type='hidden' name='password_reset_page' value='<?php echo $settings['password_reset_page'];?>'>
	<input type='hidden' name='register_page' value='<?php echo $settings['register_page'];?>'>
	<input type='hidden' name='2fa_page' value='<?php echo $settings['2fa_page'];?>'>
	<?php
	return ob_get_clean();
}, 10, 3);

add_filter('sim_email_settings', function($optionsHtml, $moduleSlug, $settings){
	//module slug should be the same as grandparent folder name
	if($moduleSlug != MODULE_SLUG){
		return $optionsHtml;
	}

	ob_start();
	?>
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
	<?php
	return ob_get_clean();
}, 10, 3);

add_filter('sim_module_updated', function($newOptions, $moduleSlug, $oldOptions){
	//module slug should be the same as grandparent folder name
	if($moduleSlug != MODULE_SLUG){
		return $newOptions;
	}

	$publicCat	= get_cat_ID('Public');

	// Create password reset page
	$pageId	= SIM\getModuleOption($moduleSlug, 'password_reset_page');
	// Only create if it does not yet exist
	if(!$pageId || get_post_status($pageId) != 'publish'){
		$post = array(
			'post_type'		=> 'page',
			'post_title'    => 'Change password',
			'post_content'  => '[change_password]',
			'post_status'   => "publish",
			'post_author'   => '1',
			'post_category'	=> [$publicCat]
		);
		$pageId 	= wp_insert_post( $post, true, false);

		//Store page id in module options
		$newOptions['password_reset_page']	= $pageId;

		// Do not require page updates
		update_post_meta($pageId, 'static_content', true);
	}

	// Add registration page
	if(isset($newOptions['user_registration'])){
		// Create register page
		$pageId	= SIM\getModuleOption($moduleSlug, 'register_page');
		// Only create if it does not yet exist
		if(!$pageId || get_post_status($pageId) != 'publish'){
			$post = array(
				'post_type'		=> 'page',
				'post_title'    => 'Request user account',
				'post_content'  => '[request_account]',
				'post_status'   => "publish",
				'post_author'   => '1',
				'post_category'	=> [$publicCat]
			);
			$pageId 	= wp_insert_post( $post, true, false);

			//Store page id in module options
			$newOptions['register_page']	= $pageId;

			// Do not require page updates
			update_post_meta($pageId,'static_content', true);
		}
	}

	// Add 2fa page
	$pageId	= SIM\getModuleOption($moduleSlug, '2fa_page');
	// Only create if it does not yet exist
	if(!$pageId || get_post_status($pageId) != 'publish'){
		$post = array(
			'post_type'		=> 'page',
			'post_title'    => 'Two Factor Authentication',
			'post_content'  => '[twofa_setup]',
			'post_status'   => "publish",
			'post_author'   => '1'
		);
		$pageId 	= wp_insert_post( $post, true, false);

		//Store page id in module options
		$newOptions['2fa_page']	= $pageId;

		// Do not require page updates
		update_post_meta($pageId, 'static_content', true);
	}

	// Remove registration page
	if(isset($oldOptions['register_page']) && !isset($newOptions['user_registration'])){
		wp_delete_post($oldOptions['register_page'], true);
		unset($newOptions['register_page']);
	}

	return $newOptions;

}, 10, 3);

add_filter('display_post_states', function ( $states, $post ) { 
    
    if ( $post->ID == SIM\getModuleOption(MODULE_SLUG, 'password_reset_page') ) {
        $states[] = __('Password reset page'); 
    }elseif ( $post->ID == SIM\getModuleOption(MODULE_SLUG, 'register_page') ) {
        $states[] = __('User register page'); 
    }elseif ( $post->ID == SIM\getModuleOption(MODULE_SLUG, '2fa_page') ) {
        $states[] = __('Two Factor Setup page'); 
    } 

    return $states;
}, 10, 2);

add_action('sim_module_deactivated', function($moduleSlug, $options){
	//module slug should be the same as grandparent folder name
	if($moduleSlug != MODULE_SLUG)	{return;}

	// Remove the auto created page
	wp_delete_post($options['password_reset_page'], true);
	wp_delete_post($options['register_page'], true);
}, 10, 2);