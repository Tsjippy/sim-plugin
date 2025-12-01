<?php
namespace SIM;

add_action("sim-github-before-updating-module-login", __NAMESPACE__.'\preUpdate', 10, 2);
function preUpdate($oldVersion, $newVersion){
    SIM\printArray($oldVersion);

    // Include the necessary file for activate_plugin()
    require_once( ABSPATH . 'wp-admin/includes/plugin.php' );

    // Define the path to the plugin's main file relative to wp-content/plugins/
    $pluginPath = 'sim-base/sim-base.php';

    // Check if the plugin is not already active
    if ( ! is_plugin_active( $pluginPath ) ) {
        // Activate the plugin
        activate_plugin( $pluginPath );
    }
}