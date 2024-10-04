<?php
namespace SIM\FRONTENDPOSTING;
use SIM;

const MODULE_VERSION		= '7.0.44';
//module slug is the same as grandparent folder name
DEFINE(__NAMESPACE__.'\MODULE_SLUG', strtolower(basename(dirname(__DIR__))));

DEFINE(__NAMESPACE__.'\MODULE_PATH', plugin_dir_path(__DIR__));

add_filter('sim_submenu_description', function($description, $moduleSlug){
	//module slug should be the same as the constant
	if($moduleSlug != MODULE_SLUG)	{
		return $description;
	}

	ob_start();

	?>
	<p>
		This module makes it possible to add and edit pages, posts and custom post types.<br>
		Just place this shortcode on any page: <code>[front_end_post]</code>.<br>
		An overview of the posts created by the current user can be displayed using the: <code>[your_posts]</code> shortcode.<br>
		If anyone without publish rights tries to add or edit a page, it will be stored as pending.<br>
		An overview of pending content can be shown using the <code>[pending_pages]</code> shortcode.<br>
		You can use the <code>[pending_post_icon]</code> shortcode as an indicator, displaying the amount of pending posts in menu items.<br>
		This module also adds a custom post status: archived. Meaning a post is not visible but still kept for reference
	</p>

	<?php
	$url		= SIM\ADMIN\getDefaultPageLink($moduleSlug, 'front_end_post_pages');
	$url2		= SIM\ADMIN\getDefaultPageLink($moduleSlug, 'pending_pages');
	
	if(!empty($url)){
		?>
		<p>
			<strong>Auto created pages:</strong><br>
			<a href='<?php echo $url;?>'>Add content</a><br>
			<a href='<?php echo $url2;?>'>Pending pages</a>
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

	if(!isset($settings['pending-channels']) || !is_array($settings['pending-channels'])){
		$settings['pending-channels']	= [];
	}

	ob_start();
	?>
	<label>Content manager role</label>
	<select name='content-manager-roles[]' multiple='multiple'>
		<?php
		global $wp_roles;
	
		//Get all available roles
		$userRoles	= $wp_roles->role_names;
		
		//Sort the roles
		asort($userRoles);

		if(!isset($settings['content-manager-roles'])){
			$settings['content-manager-roles']	= [];
		}

		foreach($userRoles as $key=>$role){
			$selected	= '';
			if(is_array($settings['content-manager-roles']) && in_array($key, $settings['content-manager-roles'])){
				$selected	= 'selected=selected';
			}
			echo "<option value='$key' $selected>$role</option>";
		}
		?>
	</select>
	<br>
	<label>How should content managers be notified about pending content?</label>
	<br>
	<label><input type='checkbox' name='pending-channels[]' value='email' <?php if(is_array($settings['pending-channels']) && in_array('email', $settings['pending-channels'])){echo 'checked';}?>>E-mail</label>
	<label><input type='checkbox' name='pending-channels[]' value='signal' <?php if(is_array($settings['pending-channels']) && in_array('signal', $settings['pending-channels'])){echo 'checked';}?>>Signal</label>
	<br>
	<br>

	<label>How often should people be reminded of content which should be updated?</label>
	<select name="page_age_reminder">
		<?php
		SIM\ADMIN\recurrenceSelector($settings['page_age_reminder']);
		?>
	</select>
	<br>
	<label>What should be the max time in months for a page without any changes?</label>
	<select name="max_page_age">
		<?php
		for ($x = 0; $x <= 12; $x++) {
			if($settings['max_page_age'] === strval($x)){
				$selected = 'selected="selected"';
			}else{
				$selected = '';
			}
			echo "<option value='$x' $selected>$x</option>";
		}
		?>
	</select>
	<br>
	<label>
		Post status after expiry<br>
		<label>
			<input type='radio' name='expired-post-type' id='expired-post-type' value='trash' <?php if($settings['expired-post-type'] == 'trash'){echo 'checked';}?>>
			Trashed
		</label>

		<label>
			<input type='radio' name='expired-post-type' id='expired-post-type' value='archived' <?php if($settings['expired-post-type'] == 'archived'){echo 'checked';}?>>
			Archived
		</label>
	</label>
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
	<h4>E-mail send to people when a page is out of date</h4>
	<label>
		Define the e-mail people get when they are responsible for a page which is out of date.<br>
		You can use placeholders in your inputs.<br>
		These ones are available (click on any of them to copy):
	</label>
	<?php
	$email    = new PostOutOfDateEmail(wp_get_current_user());
	$email->printPlaceholders();
	$email->printInputs($settings);
	?>
	<br>
	<br>
	<label>
		Define the e-mail people get when they are responsible for multiple pages which is out of date.<br>
		You can use placeholders in your inputs.<br>
		These ones are available (click on any of them to copy):
	</label>
	<?php
	$email    = new PostOutOfDateEmails(wp_get_current_user());
	$email->printPlaceholders();
	$email->printInputs($settings);
	?>
	<br>
	<br>
	<h4>E-mail send to content managers when a post is pending</h4>
	<label>
		Define the e-mail content managers get when someone has submitted a post or post update for review<br>
	</label>
	<?php
	$email    = new PendingPostEmail(wp_get_current_user());
	$email->printPlaceholders();
	$email->printInputs($settings);
	?>
	<br>
	<br>
	<h4>E-mail send to authors when their content is approved</h4>
	<label>
		Define the e-mail authors when their post is approved<br>
	</label>
	<?php
	$email    = new ApprovedPostMail(wp_get_current_user());
	$email->printPlaceholders();
	$email->printInputs($settings);

	return ob_get_clean();
}, 10, 3);

add_filter('sim_module_updated', function($options, $moduleSlug, $oldOptions){
	//module slug should be the same as grandparent folder name
	if($moduleSlug != MODULE_SLUG){
		return $options;
	}

	// Create frontend posting page
	$options	= SIM\ADMIN\createDefaultPage($options, 'front_end_post_pages', 'Add content', '[front_end_post]', $oldOptions);

	$options	= SIM\ADMIN\createDefaultPage($options, 'pending_pages', 'Pending Posts', '[pending_pages]', $oldOptions);

	scheduleTasks();

	return $options;
}, 10, 3);

add_filter('display_post_states', function ( $states, $post ) {

    if ( in_array($post->ID, SIM\getModuleOption(MODULE_SLUG, 'front_end_post_pages', false))) {
        $states[] = __('Frontend posting page');
    }elseif ( in_array($post->ID, SIM\getModuleOption(MODULE_SLUG, 'pending_pages', false))) {
        $states[] = __('Pending posts page');
    }

    return $states;
}, 10, 2);

add_action('sim_module_deactivated', function($moduleSlug, $options){
	//module slug should be the same as grandparent folder name
	if($moduleSlug != MODULE_SLUG)	{
		return;
	}

	foreach($options['front_end_post_pages'] as $page){
		// Remove the auto created page
		wp_delete_post($page, true);
	}
}, 10, 2);