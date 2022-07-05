<?php
namespace SIM\CONTENTFILTER;
use SIM;

const MODULE_VERSION		= '7.0.2';
//module slug is the same as grandparent folder name
DEFINE(__NAMESPACE__.'\MODULE_SLUG', basename(dirname(dirname(__FILE__))));

add_action('sim_module_activated', function($moduleSlug){
	//module slug should be the same as grandparent folder name
	if($moduleSlug != MODULE_SLUG)	{return;}
	
	//Create a public category if it does not exist
	wp_create_category('Public');
	wp_create_category('Confidential');
});

add_filter('sim_submenu_description', function($description, $moduleSlug){
	//module slug should be the same as the constant
	if($moduleSlug != MODULE_SLUG)	{
		return $description;
	}

	ob_start();
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

	return ob_get_clean();
}, 10, 2);

add_filter('sim_submenu_options', function($optionsHtml, $moduleSlug){
	//module slug should be the same as grandparent folder name
	if($moduleSlug != MODULE_SLUG){
		return $optionsHtml;
	}

	ob_start();

	$default    = SIM\getModuleOption('content_filter', 'default_status');
	
    ?>
	<label>
		<input type="checkbox" name="default_status" value="private" <?php if($default == 'private'){echo 'checked';}?>>
		Make uploaded media private by default
	</label>
	<?php
	return ob_get_clean();
}, 10, 2);