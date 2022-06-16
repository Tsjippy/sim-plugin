<?php
namespace SIM\BULKCHANGE;
use SIM;

const MODULE_VERSION		= '7.0.1';
DEFINE(__NAMESPACE__.'\MODULE_SLUG', basename(dirname(dirname(__FILE__))));

add_action('sim_submenu_description', function($moduleSlug){
	//module slug should be the same as grandparent folder name
	if($moduleSlug != MODULE_SLUG)	{return;}

	?>
	<p>
		This module add one shortcode: 'bulk_update_meta'.<br>
		That shortcode allows you to update a specific meta key for all users on one page.<br>
		This shortcode comes with 5 possible keys:
		<h4>roles</h4>
		Defines the roles with permission to use this page.<br>
		<h4>inversed</h4>
		To be used in combination with roles. Instead of roles defining who has access, it now defines wh has no access.<br>
		<h4>key</h4>
		Defines the meta key to be updated.<br>
		In case of a nested meta key use the #.<br>
		<h4>folder</h4>
		In case of a file upload defines the folder where the files should go to.<br>
		<h4>family</h4>
		Defines whether the meta key should be updated for all family members as well.<br>
		<br>
		Use like this: <code>[bulk_update_meta roles="visainfo,administrator" key="visa_info#nin#"]</code>
	</p>
	<?php
});