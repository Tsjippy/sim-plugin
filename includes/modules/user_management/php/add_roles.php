<?php
namespace SIM\USERMANAGEMENT;
use SIM;

add_filter('sim_module_updated', function($options, $module_slug){
	//module slug should be the same as grandparent folder name
	if($module_slug != basename(dirname(dirname(__FILE__))))	return $options;

	// Only add the new role if it does not exist
	if(!wp_roles()->is_role( 'rolemanagement' )){
		$roleSet 					= get_role( 'contributor' )->capabilities;
		$roleSet['edit_users']		= true;
		$roleSet['list_users']		= true;
		$roleSet['promote_users']	= true;

		add_role( 
			'rolemanagement', 
			'Role Manager', 
			$roleSet
		);
	}

	if(!wp_roles()->is_role( 'usermanagement' )){
		$roleSet 					= get_role( 'contributor' )->capabilities;
		$roleSet['edit_users']		= true;
		$roleSet['list_users']		= true;
		$roleSet['remove_users']	= true;
		$roleSet['promote_users']	= true;
		
		add_role( 
			'usermanagement', 
			'User Manager', 
			$roleSet
		);
	}

	return $options;
}, 10, 2);

add_filter('sim_role_description', function($description, $role){
    switch($role){
        case 'rolemanagement':
            return 'Ability to grant people an extra role';
		case 'usermanagement':
			return 'Ability to edit other user accounts';
    }
    return $description;
}, 10, 2);