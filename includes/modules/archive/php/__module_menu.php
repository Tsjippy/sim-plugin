<?php
namespace SIM\ARCHIVE;
use SIM;

const MODULE_VERSION		= '7.0.0';
DEFINE(__NAMESPACE__.'\MODULE_SLUG', strtolower(basename(dirname(__DIR__))));

add_filter('sim_submenu_description', function($description, $moduleSlug){
	//module slug should be the same as the constant
	if($moduleSlug != MODULE_SLUG)	{
		return $description;
	}
	ob_start();
	?>
	<p>
		This module makes it possible to archive any content instead of deleting it.<br>
		Content no longer will be visible for users, but is still available for re-publishing<br>
	</p>
	<?php

	return ob_get_clean();
}, 10, 2);


add_filter('sim_module_data', function($dataHtml, $moduleSlug, $settings){
	//module slug should be the same as grandparent folder name
	if($moduleSlug != MODULE_SLUG){
		return $dataHtml;
	}

	return $dataHtml;
}, 10, 3);
