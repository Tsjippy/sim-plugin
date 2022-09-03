<?php
namespace SIM\USERMANAGEMENT;
use SIM;

const MODULE_VERSION		= '7.0.7';
//module slug is the same as grandparent folder name
DEFINE(__NAMESPACE__.'\MODULE_SLUG', strtolower(basename(dirname(__DIR__))));

add_filter('sim_submenu_description', function($description, $moduleSlug){
	global $Modules;
	
	//module slug should be the same as the constant
	if($moduleSlug != MODULE_SLUG)	{
		return $description;
	}

	ob_start();
	?>
	<p>
		This module depends on the forms module. The forms module will be automatically activated upon activation.<br>
		<br>
		This module adds 5 shortcodes:
		<h4>user-info</h4>
		This shortcode displays all forms to set and update userdata.<br>
		You can also change userdata for other users if you have the 'usermanagement' role.<br>
		Use like this: <code>[user-info currentuser='true']</code>
		<br>
		<h4>userstatistics</h4>
		This shortcode displays a table listing all website users and some of their details.<br>
		Use like this: <code>[userstatistics]</code>
		<br>
		<h4>create_user_account</h4>
		This shortcode displays a from to create new user accounts.<br>
		Use like this: <code>[create_user_account]</code>
		<br>
		<h4>pending_user</h4>
		This shortcode displays all user account who are pending approval.<br>
		Use like this: <code>[pending_user]</code>
		<br>
		<h4>change_password</h4>
		This shortcode displays a form for users to reset their password.<br>
		Use like this: <code>[change_password]</code>
	</p>
	<?php
	$links		= [];
	$url		= SIM\ADMIN\getDefaultPageLink($moduleSlug, 'account_page');
	if(!empty($url)){
		$links[]	= "<a href='$url'>Account</a><br>";
	}

	$url		= SIM\ADMIN\getDefaultPageLink($moduleSlug, 'user_edit_page');
	if(!empty($url)){
		$links[]	= "<a href='$url'>Edit users</a><br>";
	}

	$url		= SIM\ADMIN\getDefaultPageLink($moduleSlug, 'account_create_page');
	if(!empty($url)){
		$links[]	= "<a href='$url'>Create user accounts</a><br>";
	}

	$url		= SIM\ADMIN\getDefaultPageLink($moduleSlug, 'pending_users_page');
	if(!empty($url)){
		$links[]	= "<a href='$url'>Pending user accounts</a><br>";
	}

	if(!empty($links)){
		?>
		<p>
			<strong>Auto created pages:</strong><br>
			<?php
			foreach($links as $link){
				echo $link;
			}
			?>
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
	<label>
		<input type='checkbox' name='tempuser' value='tempuser' <?php if(isset($settings['tempuser'])){echo 'checked';}?>>
		Enable temporary user accounts
	</label>
	<br>
	<label>How long in advance (in months) should we warn people about vaccinations who are about to expire?</label>
	<select name="vaccination_warning_freq">
		<?php
		SIM\ADMIN\recurrenceSelector($settings['vaccination_warning_freq']);
		?>
	</select>
	<br>
	<label for="greencard_reminder_freq">How often should people be reminded of their greencard expiry?</label>
	<br>
	<select name="greencard_reminder_freq">
		<?php
		SIM\ADMIN\recurrenceSelector($settings['greencard_reminder_freq']);
		?>
	</select>
	<br>
	<label for="check_details_mail_freq">How often should people asked to check their details for changes?</label>
	<br>
	<select name="check_details_mail_freq">
		<?php
		SIM\ADMIN\recurrenceSelector($settings['check_details_mail_freq']);
		?>
	</select>
	<br>
	<label>
		E-mail address of the healthcare coordinator<br>
		<input type="email" name="health_email" value="<?php echo $settings['health_email'];?>">
	</label>
	<br>
	<label>
		E-mail address of the personnel coordinator<br>
		<input type="email" name="personnel_email" value="<?php echo $settings['personnel_email'];?>">
	</label>
	<br>
	<label>
		E-mail address of the short term coordinator<br>
		<input type="email" name="sta_email" value="<?php echo $settings['sta_email'];?>">
	</label>
	<br>
	<br>
	<label>Select any forms you want to be available on the account page</label>
	<br>
	<?php

	foreach(['family', 'generic', 'location', 'profile picture', 'security', 'vaccinations'] as $form){
		if(in_array($form, $settings['enabled-forms'])){
			$checked	= 'checked';
		}else{
			$checked	= '';
		}

		echo "<label>";
			echo "<input type='checkbox' name='enabled-forms[]' value='$form' $checked>";
			echo ucfirst($form);
		echo "</label><br>";
	}

	return ob_get_clean();
}, 10, 3);

add_filter('sim_email_settings', function($optionsHtml, $moduleSlug, $settings){
	//module slug should be the same as grandparent folder name
	if($moduleSlug != MODULE_SLUG){
		return $optionsHtml;
	}

	ob_start();
	?>
	<h4>E-mail to people who's account is just approved</h4>
	<label>Define the e-mail people get when they are added to the website</label>
	<?php
	$accountApproveddMail    = new AccountApproveddMail(wp_get_current_user());
	$accountApproveddMail->printPlaceholders();
	$accountApproveddMail->printInputs($settings);
	?>
	<br>
	<br>

	<h4>E-mail to people who's account is just created</h4>
	<label>Define the e-mail people get when they are added to the website</label>
	<?php
	$accountCreatedMail    = new AccountCreatedMail(wp_get_current_user());
	$accountCreatedMail->printPlaceholders();
	$accountCreatedMail->printInputs($settings);
	?>
	<br>
	<br>

	<h4>E-mail to people who's account is about to expire</h4>
	<label>Define the e-mail people get when they are about to be removed from the website</label>
	<?php
	$accountExpiryMail    = new AccountExpiryMail(wp_get_current_user());
	$accountExpiryMail->printPlaceholders();
	$accountExpiryMail->printInputs($settings);
	?>
	<br>
	<br>

	<h4>E-mail to people who's account is deleted</h4>
	<label>Define the e-mail people get when they are removed from the website</label>
	<?php
	$accountRemoveMail    = new AccountRemoveMail(wp_get_current_user());
	$accountRemoveMail->printPlaceholders();
	$accountRemoveMail->printInputs($settings);
	?>
	<br>
	<br>

	<h4>E-mail to people who have not logged in for more than a year</h4>
	<label>Define the e-mail people get when they have not logged into the website for more than a year</label>
	<?php
	$weMissYouMail    = new WeMissYouMail(wp_get_current_user());
	$weMissYouMail->printPlaceholders();
	$weMissYouMail->printInputs($settings);
	?>
	<br>
	<br>

	<h4>E-mail to people who's vaccinations are about to expire</h4>
	<label>Define the e-mail people get when one or more vaccinations are about to expire</label>
	<?php
	$vaccinationWarningMail    = new AdultVaccinationWarningMail(wp_get_current_user());
	$vaccinationWarningMail->printPlaceholders();
	$vaccinationWarningMail->printInputs($settings);
    ?>
	<br>
	<br>

	<h4>E-mail to parents who's children have vaccinations who are about to expire</h4>
	<label>Define the e-mail people get when one or more vaccinations of a child are about to expire</label>
	<?php
	$vaccinationWarningMail    = new ChildVaccinationWarningMail(wp_get_current_user());
	$vaccinationWarningMail->printPlaceholders();
	$vaccinationWarningMail->printInputs($settings);
	?>
	<br>
	<br>

	<h4>E-mail when the greencard is about to expire</h4>
	<label>Define the e-mail people get when one or more vaccinations of a child are about to expire</label>
	<?php
	$greenCardReminderMail    = new GreenCardReminderMail(wp_get_current_user());
	$greenCardReminderMail->printPlaceholders();
	$greenCardReminderMail->printInputs($settings);

	return ob_get_clean();
}, 10, 3);

add_filter('sim_module_updated', function($options, $moduleSlug, $oldOptions){
	//module slug should be the same as grandparent folder name
	if($moduleSlug != MODULE_SLUG){
		return $options;
	}

	// Create account page
	$options	= SIM\ADMIN\createDefaultPage($options, 'account_page', 'Account', '[user-info currentuser=true]', $oldOptions);

	// Create user edit page
	$options	= SIM\ADMIN\createDefaultPage($options, 'user_edit_page', 'Edit users', '[user-info]', $oldOptions);

	// Create user create page
	$options	= SIM\ADMIN\createDefaultPage($options, 'account_create_page', 'Add user account', '[create_user_account]', $oldOptions);

	// Create pending users page
	$options	= SIM\ADMIN\createDefaultPage($options, 'pending_users_page', 'Pending user accounts', '[pending_user]', $oldOptions);
	
	scheduleTasks();

	return $options;
}, 10, 3);

add_filter('display_post_states', function ( $states, $post ) { 
    
	if ( in_array($post->ID, SIM\getModuleOption(MODULE_SLUG, 'account_page'))) {
		$states[] = __('Account page'); 
	}elseif(in_array($post->ID, SIM\getModuleOption(MODULE_SLUG, 'user_edit_page')) ) {
		$states[] = __('User edit page'); 
	}elseif(in_array($post->ID, SIM\getModuleOption(MODULE_SLUG, 'account_create_page'))) {
		$states[] = __('Account create page'); 
	}elseif(in_array($post->ID, SIM\getModuleOption(MODULE_SLUG, 'pending_users_page'))) {
		$states[] = __('Pending users page'); 
	}

	return $states;
}, 10, 2);


add_action('sim_module_deactivated', function($moduleSlug, $options){
	//module slug should be the same as grandparent folder name
	if($moduleSlug != MODULE_SLUG)	{
		return;
	}

	$removePages	= [];
	
	if(is_array($options['account_page'])){
		$removePages	= array_merge($removePages, $options['2fa_page']);
	}

	if(is_array($options['user_edit_page'])){
		$removePages	= array_merge($removePages, $options['user_edit_page']);
	}

	if(is_array($options['account_create_page'])){
		$removePages	= array_merge($removePages, $options['account_create_page']);
	}

	if(is_array($options['pending_users_page'])){
		$removePages	= array_merge($removePages, $options['pending_users_page']);
	}

	// Remove the auto created pages
	foreach($removePages as $page){
		// Remove the auto created page
		wp_delete_post($page, true);
	}
}, 10, 2);

add_action('sim_module_activated', function($moduleSlug){
	//module slug should be the same as grandparent folder name
	if($moduleSlug != MODULE_SLUG)	{
		return;
	}

	// Enable forms module
	if(!SIM\getModuleOption('forms', 'enable')){
		SIM\ADMIN\enableModule('forms', ['enable'=>'on']);
	}

	// Import the forms
	$formBuilder	= new SIM\FORMS\FormBuilderForm();

	$files = glob(__DIR__  . "/../imports/*.sform");
	foreach ($files as $file) {
		$formBuilder->importForm($file);   
	}
});