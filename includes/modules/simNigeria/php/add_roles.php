<?php
namespace SIM\SIMNIGERIA;
use SIM;

add_filter('sim_module_updated', function($options, $moduleSlug){
	//module slug should be the same as grandparent folder name
	if($moduleSlug != MODULE_SLUG){
		return $options;
	}

	$roleSet = get_role( 'contributor' )->capabilities;

	// Only add the new role if it does not exist
	if(!wp_roles()->is_role( 'financialinfo' )){
		add_role(
			'financialinfo',
			'Financial info',
			$roleSet
		);
	}

	if(!wp_roles()->is_role( 'housingmanagement' )){
		add_role(
			'housingmanagement',
			'Housing management',
			$roleSet
		);
	}

	if(!wp_roles()->is_role( 'medicalinfo' )){
		add_role(
			'medicalinfo',
			'Medical info',
			$roleSet
		);
	}

	if(!wp_roles()->is_role( 'nigerianstaff' )){
		add_role(
			'nigerianstaff',
			'Nigerian Staff',
			$roleSet
		);
	}

	if(!wp_roles()->is_role( 'travelinfo' )){
		add_role(
			'travelinfo',
			'Travel info',
			$roleSet
		);
	}

	if(!wp_roles()->is_role( 'visainfo' )){
		add_role(
			'visainfo',
			'Visa info',
			$roleSet
		);
	}

	return $options;
}, 10, 2);

add_filter('sim_role_description', function($description, $role){
    switch($role){
        case 'financialinfo':
            return 'Access to fincane form results';
        case 'housingmanagement':
            return 'Access to repair request form results';
        case 'housingreservation':
            return 'Access to the accommodation reservation form';
        case 'medicalinfo':
            return 'Access to medical info';
        case 'nigerianstaff':
            return 'Nigerian Office Staff';
        case 'travelinfo':
            return 'Acces to travel form results';
		case 'visainfo':
			return 'Access to Immigration data';
		default:
			return $description;
    }
}, 10, 2);

add_action('sim_roles_changed', function($user, $newRoles){
    //Check if new roles require mailchimp actions
    $Mailchimp = new SIM\MAILCHIMP\Mailchimp($user->ID);

	if(in_array('nigerianstaff', $newRoles)){
		//Role changed to nigerianstaff, remove tags
		$tags = explode(',', $Mailchimp->settings['missionary_tags']);
		$Mailchimp->changeTags($tags, 'inactive');
		//Add office staff tags
		$Mailchimp->changeTags(explode(',', $Mailchimp->settings['office_staff_tags']), 'active');
	}
	
	if(in_array('nigerianstaff', $Mailchimp->user->roles) && !in_array('nigerianstaff', $newRoles)){
		//Nigerian staff role is removed
		$tags = array_merge(explode(',', $Mailchimp->settings['user_tags']), explode(',', $Mailchimp->settings['missionary_tags']));
		$Mailchimp->changeTags($tags, 'active');
	}
}, 10, 2);