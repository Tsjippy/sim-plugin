<?php
namespace SIM\EMBEDPAGE;
use SIM;

const ModuleVersion		= '7.0.0';

add_action('sim_submenu_description', function($moduleSlug, $moduleName){
	//module slug should be the same as grandparent folder name
	if($moduleSlug != basename(dirname(dirname(__FILE__))))	return;

	?>
	<p>
		This module makes it possible to display the contents of another page in a page.<br>
		This can be done by using the frontend contend module or by using the 'embed_page' shortcode.<br>
		Use like this: <code>[embed_page id=SOMEPAGEID]</code>
	</p>
	<?php
},10,2);

add_action('sim_submenu_options', function($moduleSlug, $moduleName, $settings){
	//module slug should be the same as grandparent folder name
	if($moduleSlug != basename(dirname(dirname(__FILE__))))	return;
	
    ?>

	<?php
}, 10, 3);