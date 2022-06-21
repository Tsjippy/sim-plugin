<?php
namespace SIM\PRAYER;
use SIM;

add_filter('sim_module_updated', function($options, $moduleSlug){
	//module slug should be the same as grandparent folder name
	if($moduleSlug != MODULE_SLUG){
		return $options;
	}

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
    if($role == 'prayercoordinator'){
        return 'Ability to publish prayer requests';
    }
    return $description;
}, 10, 2);