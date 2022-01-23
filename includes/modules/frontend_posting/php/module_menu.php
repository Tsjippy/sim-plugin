<?php
namespace SIM;

add_action('sim_submenu_options', function($module_slug, $module_name, $settings){
	//module slug should be the same as grandparent folder name
	if($module_slug != basename(dirname(dirname(__FILE__))))	return;
	
	?>
	<label for="publish_post_page">Page with the post edit form</label>
	<?php
	echo page_select("publish_post_page",$settings["publish_post_page"]);
    
}, 10, 3);