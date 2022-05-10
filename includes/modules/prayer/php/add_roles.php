<?php
namespace SIM\PRAYER;
use SIM;

add_filter('sim_module_updated', function($options, $module_slug){
	//module slug should be the same as grandparent folder name
	if($module_slug != basename(dirname(dirname(__FILE__))))	return $options;

	$roleSet = get_role( 'contributor' )->capabilities;

	// Only add the new role if it does not exist
	if(!wp_roles()->is_role( 'prayercoordinator' )){
		add_role( 
			'prayercoordinator', 
			'Prayer coordinator', 
			$roleSet
		);
	}

	return $options;
}, 10, 2);

add_filter('sim_role_description', function($description, $role){
    switch($role){
        case 'prayercoordinator':
            return 'Ability to publish prayer requests';
    }
    return $description;
}, 10, 2);