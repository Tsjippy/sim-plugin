<?php
namespace SIM\GITHUB;
use SIM;

const MODULE_VERSION		= '8.0.0';
DEFINE(__NAMESPACE__.'\MODULE_SLUG', strtolower(basename(dirname(__DIR__))));

DEFINE(__NAMESPACE__.'\MODULE_PATH', plugin_dir_path(__DIR__));

require( MODULE_PATH  . 'lib/vendor/autoload.php');

add_filter('sim_submenu_github_description', __NAMESPACE__.'\subMenuDescription');
function subMenuDescription($description){
	ob_start();
	?>
	<p>
		This module makes it possible to check for github releases and downloads them if needed
	</p>
	<?php

	return $description.ob_get_clean();
}

add_filter('sim_submenu_github_options', __NAMESPACE__.'\subMenuOptions', 10, 2);
function subMenuOptions($optionsHtml, $settings){
	ob_start();
	
    ?>
	<label>
		Github access token. Needed to access private repositories.<br>
		Create one <a href='https://github.com/settings/tokens/new'>here</a>.<br>
		<input type='text' name='token' value='<?php echo esc_attr($settings['token']);?>' style='min-width:300px'>
	</label>
	<br>
	<br>
	<label>
		<input type="checkbox" name="auto-download" value="1" <?php if(!empty($settings['auto-download'])){echo "checked";}?>>
		Auto download new releases of modules.
	</label>

	<?php

	return $optionsHtml.ob_get_clean();
}

add_filter('sim_module_github_after_save', __NAMESPACE__.'\moduleUpdated');
function moduleUpdated($newOptions){
	scheduleTasks();

	return $newOptions;
}