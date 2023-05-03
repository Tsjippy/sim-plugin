<?php
namespace SIM\MANDATORY;
use SIM;

add_filter('sim_module_updated', function($options, $moduleSlug){
	//module slug should be the same as grandparent folder name
	if($moduleSlug != MODULE_SLUG){
		return $options;
	}

	$roleSet = get_role( 'contributor' )->capabilities;

	// Only add the new role if it does not exist
	if(!wp_roles()->is_role( 'no_man_docs' )){
		add_role(
			'no_man_docs',
			'No mandatory documents',
			$roleSet
		);
	}

	return $options;
}, 10, 2);

add_filter('sim_role_description', function($description, $role){
    if($role == 'no_man_docs'){
		return "Mandatory documents do not apply";
	}

    return $description;
}, 10, 2);