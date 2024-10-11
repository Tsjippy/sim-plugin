<?php
namespace SIM\GITHUB;
use SIM;

const MODULE_VERSION		= '8.0.0';
DEFINE(__NAMESPACE__.'\MODULE_SLUG', strtolower(basename(dirname(__DIR__))));

DEFINE(__NAMESPACE__.'\MODULE_PATH', plugin_dir_path(__DIR__));

require( MODULE_PATH  . 'lib/vendor/autoload.php');

add_filter('sim_submenu_description', function($description, $moduleSlug, $moduleName){
	//module slug should be the same as the constant
	if($moduleSlug != MODULE_SLUG)	{
		return $description;
	}

	ob_start();
	?>
	<p>
		This module makes it possible to check for github releases and downloads them if needed
	</p>
	<?php

	return ob_get_clean();
}, 10, 3);

add_filter('sim_submenu_options', function($optionsHtml, $moduleSlug, $settings, $moduleName){
	//module slug should be the same as grandparent folder name
	if($moduleSlug != MODULE_SLUG){
		return $optionsHtml;
	}

	ob_start();
	
    ?>
	<label>
		Github access token. Needed to access private repositories.<br>
		Create one <a href='https://github.com/settings/tokens/new'>here</a>.<br>
		<input type='text' name='token' value='<?php echo $settings['token'];?>' style='min-width:300px'>
	</label>
	<br>
	<br>
	<label>
		<input type="checkbox" name="auto-download" value="1" <?php if(!empty($settings['auto-download'])){echo "checked";}?>>
		Auto download new releases of modules.
	</label>

	<?php

	return ob_get_clean();
}, 10, 4);