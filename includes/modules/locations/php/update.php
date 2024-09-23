<?php
namespace SIM\FORMS;
use SIM;

add_action('sim_plugin_update', function($oldVersion){
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    require_once ABSPATH . 'wp-admin/install-helper.php';

    $simForms = new SimForms();

    SIM\printArray($oldVersion);

    if($oldVersion < '2.46.9'){
        maybe_add_column($simForms->tableName, 'google_maps_api', "ALTER TABLE $simForms->tableName ADD COLUMN `google_maps_api` bool");	

        foreach(get_users() as $user){
            $location   = get_user_meta($user->id, 'location', true);
    
            if(!empty($location['compound'])){
                $location['preset']   = $location['compound'];
                unset($location['compound']);
    
                update_user_meta($user->id, 'location', $location);
            }
        }
    }
});