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
	<label for="publish_post_page">Page with the post edit form</label>
	<?php
	echo SIM\page_select("publish_post_page",$settings["publish_post_page"]);
	?>
	<br>
	<br>
	<label>How often should people be reminded of content which should be updated?</label>
	<select name="page_age_reminder">
		<option value="weekly" <?php if($settings["page_age_reminder"] == "weekly") echo 'selected';?>>Weekly</option>
		<option value="monthly" <?php if($settings["page_age_reminder"] == "monthly") echo 'selected';?>>Monthly</option>
		<option value="threemonthly" <?php if($settings["page_age_reminder"] == "threemonthly") echo 'selected';?>>Every 3 months</option>
		<option value="yearly" <?php if($settings["page_age_reminder"] == "yearly") echo 'selected';?>>Yearly</option>
	</select>
	<br>
	<label>What should be the max time in months for a page without any changes?</label>
	<select name="max_page_age">
		<?php
		for ($x = 0; $x <= 12; $x++) {
			if($settings['max_page_age'] == $x){
				$selected = 'selected';
			}else{
				$selected = '';
			}
			echo "<option value='$x' $selected>$x</option>";
		}
		?>
	</select>
	<?php
    
}, 10, 3);

add_action('sim_module_updated', function($module_slug, $options){
	//module slug should be the same as grandparent folder name
	if($module_slug != basename(dirname(dirname(__FILE__))))	return;

	schedule_tasks();
}, 10, 2);