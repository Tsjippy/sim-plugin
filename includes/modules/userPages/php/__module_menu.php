<?php
namespace SIM\USERPAGE;
use SIM;

const MODULE_VERSION		= '7.0.0';
//module slug is the same as grandparent folder name
DEFINE(__NAMESPACE__.'\MODULE_SLUG', strtolower(basename(dirname(__DIR__))));

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
		Use like this: <code>[user_link id="12"]</code>
		<br>
		<?php
	$url		= SIM\ADMIN\getDefaultPageLink($moduleSlug, 'allcontacts_pages');
	if(!empty($url)){
		?>
		<p>
			<strong>Auto created page:</strong><br>
			<a href='<?php echo $url;?>'>User overview page</a><br>
		</p>
		<?php
	}
	return ob_get_clean();
}, 10, 2);

add_filter('sim_module_updated', function($options, $moduleSlug, $oldOptions){
	//module slug should be the same as grandparent folder name
	if($moduleSlug != MODULE_SLUG){
		return $options;
	}

	// Create account page
	$options	= SIM\ADMIN\createDefaultPage($options, 'allcontacts_pages', 'All Users', '[all_contacts]', $oldOptions);

	return $options;
}, 10, 3);

add_filter('display_post_states', function ( $states, $post ) {

	if ( in_array($post->ID, SIM\getModuleOption(MODULE_SLUG, 'allcontacts_pages', 'array')) ) {
		$states[] = __('Page showing all users');
	}

	return $states;
}, 10, 2);

add_action('sim_module_deactivated', function($moduleSlug, $options){
	//module slug should be the same as grandparent folder name
	if($moduleSlug != MODULE_SLUG)	{
		return;
	}

	foreach($options['allcontacts_pages'] as $page){
		// Remove the auto created page
		wp_delete_post($page, true);
	}
}, 10, 2);