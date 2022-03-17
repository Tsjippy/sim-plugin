<?php
namespace SIM\FRONTEND_POSTING;
use SIM;

const ModuleVersion		= '7.0.0';

add_action('sim_submenu_description', function($module_slug, $module_name){
	//module slug should be the same as grandparent folder name
	if($module_slug != basename(dirname(dirname(__FILE__))))	return;

	?>
	<p>
		This module makes it possible to add and edit pages, posts and custom post types.<br>
		Just place this shortcode on any page: <code>[front_end_post]</code>.<br>
		An overview of the posts created by the current user can be displayed using the: <code>[your_posts]</code> shortcode.<br>
		If anyone without publish rights tries to add or edit a page, it will be stored as pending.<br>
		An overview of pending content can be shown using the <code>[pending_pages]</code> shortcode.<br>
		You can use the <code>[pending_post_icon]</code> shortcode as an indicator, displaying the amount of pending posts in menu items.<br>
				
	</p>
	<?php
},10,2);

add_action('sim_submenu_options', function($module_slug, $module_name, $settings){
	//module slug should be the same as grandparent folder name
	if($module_slug != basename(dirname(dirname(__FILE__))))	return;
	
	?>
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
				$selected = 'selected';
			}else{
				$selected = '';
			}
			echo "<option value='$x' $selected>$x</option>";
		}
		?>
	</select>
	<br>

	<h4>E-mail send to people when a page is out of date</h4>
	<label>
		Define the e-mail people get when they are responsible for a page which is out of date.<br>
		You can use placeholders in your inputs.<br>
		These ones are available (click on any of them to copy):
	</label>
	<?php
	$postOutOfDateEmail    = new PostOutOfDateEmail(wp_get_current_user());
	$postOutOfDateEmail->printPlaceholders();
	$postOutOfDateEmail->printInputs($settings);
	?>
	<br>
	<br>
	<h4>E-mail send to content managers when a post is pending</h4>
	<label>
		Define the e-mail content managers get when someone has submitted a post or post update for review<br>
	</label>
	<?php
	$pendingPostEmail    = new PendingPostEmail(wp_get_current_user());
	$pendingPostEmail->printPlaceholders();
	$pendingPostEmail->printInputs($settings);
    
	?>
	<input type='hidden' name='publish_post_page' value='<?php echo SIM\get_module_option($module_slug, 'publish_post_page');?>'>
	<?php
}, 10, 3);

add_action('sim_module_updated', function($module_slug, $options){
	global $Modules;

	//module slug should be the same as grandparent folder name
	if($module_slug != basename(dirname(dirname(__FILE__))))	return;

	// Create frontend posting page
	$page_id	= SIM\get_module_option($module_slug, 'publish_post_page');
	// Only create if it does not yet exist
	if(!$page_id or get_post_status($page_id) != 'publish'){
		$post = array(
			'post_type'		=> 'page',
			'post_title'    => 'Add content',
			'post_content'  => '[front_end_post]',
			'post_status'   => "publish",
			'post_author'   => '1'
		);
		$page_id 	= wp_insert_post( $post, true, false);

		//Store page id in module options
		$Modules[$module_slug]['publish_post_page']	= $page_id;

		update_option('sim_modules', $Modules);
	}

	schedule_tasks();
}, 10, 2);