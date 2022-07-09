<?php
namespace SIM\USERMANAGEMENT;
use SIM;

add_action( 'wp_enqueue_scripts', function(){
    wp_register_script( 'sim_user_management', plugins_url('js/user_management.min.js', __DIR__), array('sim_formsubmit_script'), MODULE_VERSION,true);

    $apiKey = SIM\getModuleOption(MODULE_SLUG, 'google-maps-api-key');
    if($apiKey){
        $result =wp_localize_script( 'sim_script', 
            'mapsApi', 
            $apiKey
        );
    }
}, 99);