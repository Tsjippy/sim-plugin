<?php
namespace SIM;


// Runs after a succesfull update of the plugin
add_action( 'upgrader_process_complete', __NAMESPACE__.'\upgradeSucces', 10, 2 );
function upgradeSucces( $upgraderObject, $options ) {
    // If an update has taken place and the updated type is plugins and the plugins element exists
    if ( $options['action'] == 'update' && $options['type'] == 'plugin' && isset( $options['plugins'] ) ) {
        foreach( $options['plugins'] as $plugin ) {
            // Check to ensure it's my plugin
            if( $plugin == PLUGIN ) {
                printArray('Scheduling update actions');
                $oldVersion = $upgraderObject->skin->plugin_info['Version'];

                wp_schedule_single_event(time() + 10, 'schedule_sim_plugin_update_action', [ $oldVersion ]);
            }
        }
    }
}


// Runs 10 seconds after a succesfull update of the plugin to be able to use the new files
add_action( 'schedule_sim_plugin_update_action', __NAMESPACE__.'\afterPluginUpdate');
function afterPluginUpdate($oldVersion){
    global $Modules;
    global $moduleDirs;

    printArray('Running update actions');
    do_action('sim_plugin_update', $oldVersion);

    $github = new GITHUB\Github();

    // Reinstall any missing modules
    foreach(array_keys($Modules) as $module){
        if(!in_array($module, array_keys($moduleDirs))){
            $result = $github->downloadFromGithub('Tsjippy', $module, MODULESPATH.$module);

            if($result && !is_wp_error($result)){
                printArray("Succesfully installed module $module");
            }else{
                printArray($result);
            }

        }
    }
}