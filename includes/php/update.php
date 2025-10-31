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

    if($oldVersion < '5.5.9'){
        foreach($Modules as $moduleName => &$settings){
            foreach($settings as $setting => $value){
                if(is_array($value)){
                    foreach($value as $i => $v){
                        $newIndex   = str_replace('_', '-', $i, $c);

                        if($c > 0){
                            $value[$newIndex]   = $v;

                            unset($value[$i]);
                        }
                    }
                }

                unset($settings[$setting]);

                $newIndex   = str_replace('_', '-', $setting);

                $settings[$newIndex]   = $value;
            }
        }

        update_option('sim_modules', $Modules);
    }

    if($oldVersion < '5.6.9'){
        $familyObject = new FAMILY\Family();
        $familyObject->createDbTables();

        $users  = get_users([
            'meta_key'      => 'family',
            'meta_compare'  => 'EXISTS'
        ]);

        $familyMetaKeys = apply_filters('sim-family-meta-keys', []);

        foreach($users as $user){
            $family = get_user_meta($user->ID, 'family', true);

            // Only process adults
            if(is_array($family) && !isset($family["father"]) && !isset($family["mother"])){
                foreach($family as $key => $value){
                    if(empty($value)){
                        continue;
                    }

                    switch($key){
                        case 'partner':
                            $familyObject->storeRelationship($user->ID, $value, $key, $family['weddingdate']);
                            break;
                        case 'children':
                            foreach($value as $childId){
                                $familyObject->storeRelationship($user->ID, $childId, 'child');
                            }
                            break;
                        case 'picture':
                            if(is_array($value) && !empty($value[0])){
                                $familyObject->updateFamilyMeta($user->ID, 'family_picture', $value[0]);
                            }
                            break;
                        case 'name':
                            $familyObject->updateFamilyMeta($user->ID, 'family_name', $value);
                            break;
                        case 'siblings':
                            foreach($value as $siblingId){
                                $familyObject->storeRelationship($user->ID, $siblingId, 'sibling');
                            }
                            break;
                    }
                }
        
                foreach($familyMetaKeys as $key){
                    $value   = get_user_meta($user->ID, $key, true);

                    if(empty($value)){
                        continue;
                    }

                    // Delete before updating otherwise it will be deleted again
                    delete_user_meta($user->ID, $key);

                    if($key == 'location' && is_array($value)){
                        $value   = array_values($value)[0];
                    }
                    if(!empty($value)){  
                        $familyObject->updateFamilyMeta($user->ID, $key, $value);
                    }
                }
            }else{
                foreach($familyMetaKeys as $key){
                    delete_user_meta($user->ID, $key);
                }
            }

            delete_user_meta($user->ID, 'family');
        }
    }

    if($oldVersion < '5.7.1'){
        $users = get_users([
            'meta_key'      => 'profile_picture',
            'meta_compare'  => 'EXISTS'
        ]);

        foreach($users as $user){
            $profilePicture = get_user_meta($user->ID, 'profile_picture', true);

            if(is_array($profilePicture) && isset($profilePicture[0])){
                $profilePicture = $profilePicture[0];
            }

            if(is_numeric($profilePicture) && wp_get_attachment_image_url($profilePicture)){
                update_user_meta($user->ID, 'profile_picture', $profilePicture);
            }else{
                delete_user_meta($user->ID, 'profile_picture');
            }
        }
    }
}