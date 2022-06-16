<?php
namespace SIM\USERMANAGEMENT;
use SIM;

const MODULE_VERSION		= '7.0.6';
//module slug is the same as grandparent folder name
DEFINE(__NAMESPACE__.'\MODULE_SLUG', basename(dirname(dirname(__FILE__))));

add_action('sim_submenu_description', function($moduleSlug, $moduleName){
	//module slug should be the same as grandparent folder name
	if($moduleSlug != MODULE_SLUG)	{return;}

	?>
	<p>
		This module depends on the forms module. Only activate this module when the forms module is active already.<br>
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
	$page1Id	= SIM\getModuleOption($moduleSlug, 'account_page');
	$page2Id	= SIM\getModuleOption($moduleSlug, 'user_edit_page');
	if(is_numeric($page1Id) or is_numeric($page2Id)){
		?>
		<p>
			<b>Auto created page:</b><br>
			<a href='<?php echo get_permalink($page1Id);?>'>Account</a><br>
			<a href='<?php echo get_permalink($page2Id);?>'>Edit users</a><br>
		</p>
		<?php
	}
},10,2);

add_action('sim_submenu_options', function($moduleSlug, $moduleName, $settings){
	//module slug should be the same as grandparent folder name
	if($moduleSlug != MODULE_SLUG)	{return;}
	
	?>
	<label>
		<input type='checkbox' name='tempuser' value='tempuser' <?php if(isset($settings['tempuser'])) echo 'checked';?>>
		Enable temporary user accounts
	</label>
	<br>
	<label>How long in advance (in months) should we warn people about vaccinations who are about to expire?</label>
	<select name="vaccination_warning_time">
		<?php
		for ($x = 0; $x <= 12; $x++) {
			if($settings['vaccination_warning_time'] === strval($x)){
				$selected = 'selected';
			}else{
				$selected = '';
			}
			echo "<option value='$x' $selected>$x</option>";
		}
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
}, 10, 3);

add_filter('sim_module_updated', function($options, $moduleSlug){
	//module slug should be the same as grandparent folder name
	if($moduleSlug != basename(dirname(dirname(__FILE__))))	return $options;

	// Create account page
	$pageId	= SIM\getModuleOption($moduleSlug, 'account_page');
	// Only create if it does not yet exist
	if(!$pageId or get_post_status($pageId) != 'publish'){
		$post = array(
			'post_type'		=> 'page',
			'post_title'    => 'Account',
			'post_content'  => '[user-info currentuser=true]',
			'post_status'   => "publish",
			'post_author'   => '1'
		);
		$pageId 	= wp_insert_post( $post, true, false);

		//Store page id in module options
		$options['account_page']	= $pageId;

		// Do not require page updates
		update_post_meta($pageId,'static_content', true);
	}

	// Create user edit page
	$pageId	= SIM\getModuleOption($moduleSlug, 'user_edit_page');
	// Only create if it does not yet exist
	if(!$pageId or get_post_status($pageId) != 'publish'){
		$post = array(
			'post_type'		=> 'page',
			'post_title'    => 'Edit users',
			'post_content'  => '[user-info]',
			'post_status'   => "publish",
			'post_author'   => '1'
		);
		$pageId 	= wp_insert_post( $post, true, false);

		//Store page id in module options
		$options['user_edit_page']	= $pageId;

		// Do not require page updates
		update_post_meta($pageId, 'static_content', true);
	}

	add_filter('display_post_states', function ( $states, $post ) { 
    
		if ( $post->ID == SIM\getModuleOption('user_management', 'account_page') ) {
			$states[] = __('Account page'); 
		}elseif ( $post->ID == SIM\getModuleOption('user_management', 'user_edit_page') ) {
			$states[] = __('User edit page'); 
		}
	
		return $states;
	}, 10, 2);

	scheduleTasks();

	return $options;
}, 10, 2);


add_action('sim_module_deactivated', function($moduleSlug, $options){
	//module slug should be the same as grandparent folder name
	if($moduleSlug != MODULE_SLUG)	{return;}

	// Remove the auto created page
	wp_delete_post($options['user_info_page'], true);
}, 10, 2);