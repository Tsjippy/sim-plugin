<?php
namespace SIM\QUERIER;
use SIM;

const MODULE_VERSION		= '7.0.0';
DEFINE(__NAMESPACE__.'\MODULE_SLUG', strtolower(basename(dirname(__DIR__))));

add_filter('sim_submenu_description', function($description, $moduleSlug, $moduleName){
	//module slug should be the same as the constant
	if($moduleSlug != MODULE_SLUG)	{
		return $description;
	}

	ob_start();
	?>
	<p>
		This module add the role of querier.<br>
		This allows to give them a certain permission, but not the full rights of a missionary
	<?php

	return ob_get_clean();
}, 10, 3);
