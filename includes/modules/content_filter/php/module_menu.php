<?php
namespace SIM\CONTENTFILTER;
use SIM;

const ModuleVersion		= '7.0.2';

add_action('sim_module_activated', function($moduleSlug, $options){
	//module slug should be the same as grandparent folder name
	if($moduleSlug != basename(dirname(dirname(__FILE__))))	return;
	
	//Create a public category if it does not exist
	wp_create_category('Public');
	wp_create_category('Confidential');
}, 10, 2);

add_action('sim_submenu_description', function($moduleSlug, $module_name){
	//module slug should be the same as grandparent folder name
	if($moduleSlug != basename(dirname(dirname(__FILE__))))	return;

	?>
	<p>
		This module filters all content to be only available to logged-in users.<br>
		Only content with the public category is visible to non-logged-in users.<br>
		It also makes it possible to move files to a private folder so that it is not directly accessable.<br>
		<br>
		It adds one shortcode: 'content_filter' which makes it possible to limit certain parts of a page or post to certain groups.<br>
		This shortcode has two properties: roles and inversed.<br>
		Roles define the roles who can see the content.<br>
		If inversed is set to true, roles define the roles who cannot see the content.<br>
		Use like this: <code>[content_filter roles='administrator, otherroles']This has limited visibility[/content_filter]</code>
	</p>
	<?php
},10,2);

add_action('sim_submenu_options', function($moduleSlug, $module_name, $settings){
	//module slug should be the same as grandparent folder name
	if($moduleSlug != basename(dirname(dirname(__FILE__))))	return;

	$default    = SIM\get_module_option('content_filter', 'default_status');
	
    ?>
	<label>
		<input type="checkbox" name="default_status" value="private" <?php if($default == 'private') echo 'checked';?>>
		Make uploaded media private by default
	</label>
	<?php
}, 10, 3);