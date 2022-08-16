<?php
// Filter active plugins
add_filter( 'option_active_plugins', function( $plugins ){
    // Do not load other plugins if this is a rest request for the sim plugin
    if(strpos($_SERVER['REQUEST_URI'], '/wp-json/'.RESTAPIPREFIX) !== false){
        return ['sim-plugin/sim-plugin.php'];
    }
    return $plugins;
});