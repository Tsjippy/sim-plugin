<?php
namespace SIM\ADMIN;
use SIM;

function recurrenceSelector($curFreq){
	$selected	= 'selected="selected"';
	?>
	<option value=''>---</option>
	<option value='daily' <?php if($curFreq == 'daily'){echo $selected;}?>>Daily</option>
	<option value='weekly' <?php if($curFreq == 'weekly'){echo $selected;}?>>Weekly</option>
	<option value='monthly' <?php if($curFreq == 'monthly'){echo $selected;}?>>Monthly</option>
	<option value='threemonthly' <?php if($curFreq == 'threemonthly'){echo $selected;}?>>Every quarter</option>
	<option value='sixmonthly' <?php if($curFreq == 'sixmonthly'){echo $selected;}?>>Every half a year</option>
	<option value='yearly' <?php if($curFreq == 'yearly'){echo $selected;}?>>Yearly</option>
	<?php
}

function updatePlugin($pluginFile){
	include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
	include_once ABSPATH . 'wp-admin/includes/class-plugin-upgrader.php';
	$plugin_Upgrader	= new \Plugin_Upgrader(new \Plugin_Installer_Skin( compact('title', 'url', 'nonce', 'plugin', 'api')));
	$plugin_Upgrader->upgrade($pluginFile);
	activate_plugin( $pluginFile);
}

/**
 * Installs a plugin using the wp api for that
 *
 * @param	string	$pluginFile		The relative path of the plugin file
 *
 * @return	boolean|string			true if already activated. Result if installed or activated
 */
function installPlugin($pluginFile){
	//check if plugin is already installed
	$plugins		= get_plugins();
	$activePlugins	= get_option( 'active_plugins' );
	$pluginName		= str_replace('.php', '', explode('/', $pluginFile)[1]);
	$pluginSlug		= str_replace('.php', '', explode('/', $pluginFile)[0]);
	
	if(in_array($pluginFile, $activePlugins)){
		// Already installed and activated
		return true;
	}elseif(isset($plugins[$pluginFile])){
		// Installed but not active
		activate_plugin( $pluginFile);

		if(!isset($_SESSION)){
			session_start();
		}
		$_SESSION['plugin']   = ['activated' => $pluginName];

		session_write_close();
		return 'Activated';
	}

	ob_start();
	include_once( ABSPATH . 'wp-admin/includes/plugin-install.php' );

	$api = plugins_api( 'plugin_information', array(
		'slug' => $pluginSlug,
		'fields' => array(
			'short_description' => false,
			'sections' 			=> false,
			'requires' 			=> false,
			'rating' 			=> false,
			'ratings' 			=> false,
			'downloaded' 		=> false,
			'last_updated' 		=> false,
			'added' 			=> false,
			'tags' 				=> false,
			'compatibility' 	=> false,
			'homepage' 			=> false,
			'donate_link' 		=> false,
		),
	));

	if(is_wp_error($api)){
		return ob_get_clean();
	}

	//includes necessary for Plugin_Upgrader and Plugin_Installer_Skin
	include_once( ABSPATH . 'wp-admin/includes/file.php' );
	include_once( ABSPATH . 'wp-admin/includes/misc.php' );
	include_once( ABSPATH . 'wp-admin/includes/class-wp-upgrader.php' );

	$upgrader = new \Plugin_Upgrader( new \Plugin_Installer_Skin( compact('title', 'url', 'nonce', 'plugin', 'api') ) );

	$upgrader->install($api->download_link);
	
	activate_plugin( $pluginFile);

	if(!isset($_SESSION)){
		session_start();
	}
	$_SESSION['plugin']   = ['installed' => $pluginName];

	session_write_close();

	printJs();

	return ob_get_clean();
}

function printJs(){
	echo "<script>";
		echo "document.addEventListener('DOMContentLoaded',function() {";
			echo "document.querySelector('.wrap').remove();";
			echo "document.getElementById('wpfooter').remove();";
		echo "});";
	echo "</script>";
}

