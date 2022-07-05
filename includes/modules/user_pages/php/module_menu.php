<?php
namespace SIM\USERPAGE;
use SIM;

const MODULE_VERSION		= '7.0.0';
//module slug is the same as grandparent folder name
DEFINE(__NAMESPACE__.'\MODULE_SLUG', basename(dirname(dirname(__FILE__))));

add_filter('sim_submenu_description', function($description, $moduleSlug){
	//module slug should be the same as the constant
	if($moduleSlug != MODULE_SLUG)	{
		return $description;
	}

	ob_start();
	?>
	<p>
		This module adds 3 shortcodes:
		<h4>all_contacts</h4>
		This shortcode displays a button to download all registered users to store them as contacts in gmail or outtlook.<br>
		Use like this: <code>[all_contacts']</code>
		<br>
		<h4>user_link</h4>
		This shortcode displays a user in a post or page.<br>
		It has 5 properties:<br>
		<ul>
			<li>'id' The id of the user to be displayed, mandatory</li>
			<li>'picture' Whether the users picture should be displayed or not.</li>
			<li>'phone' Whether the users phonenumbers should be displayed or not.</li>
			<li>'email' Whether the users e-mail addresses should be displayed or not.</li>
			<li>'style' Any additional html styling.</li>
		</ul>
		Use like this: <code>[pending_user id="12"]</code>
		<br>
	<?php
	return ob_get_clean();
}, 10, 2);