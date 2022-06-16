<?php
namespace SIM\EMBEDPAGE;
use SIM;

const MODULE_VERSION		= '7.0.0';
//module slug is the same as grandparent folder name
DEFINE(__NAMESPACE__.'\MODULE_SLUG', basename(dirname(dirname(__FILE__))));

add_action('sim_submenu_description', function($moduleSlug){
	//module slug should be the same as grandparent folder name
	if($moduleSlug != MODULE_SLUG)	{return;}

	?>
	<p>
		This module makes it possible to display the contents of another page in a page.<br>
		This can be done by using the frontend contend module or by using the 'embed_page' shortcode.<br>
		Use like this: <code>[embed_page id=SOMEPAGEID]</code>
	</p>
	<?php
});