<?php
// Filter active plugins
add_filter( 'option_active_plugins', function( $plugins ){
    // Do not load other plugins if this is a rest request for the sim plugin
    if(strpos($_SERVER['REQUEST_URI'], '/wp-json/sim') !== false){
        $whiteList  =[
            'sim-plugin/sim-plugin.php',
            "wp-mail-smtp-pro/wp_mail_smtp.php",
            "wp-mail-smtp/wp_mail_smtp.php",
            "really-simple-ssl/rlrsssl-really-simple-ssl.php"
        ];
        
        return [
            
        ];
    }
    return $plugins;
});