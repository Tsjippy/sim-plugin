<?php
namespace SIM\BANKING;
use SIM;


add_filter('sim_before_saving_formdata', function($formResults, $formName, $userId){
	if($formName != 'user_generics'){
		return $formResults;
	}

	$enabled	= false;

    $currentSetting = get_user_meta($userId, 'online_statements', true);
	if(is_array($currentSetting) && !empty($currentSetting)){
		$enabled	= true;
	}

	// was not enabled, send e-mail
	SIM\cleanUpNestedArray($formResults['online_statements']);
	if(!empty($formResults['online_statements']) && !$enabled){
		$user		= get_user_by('ID', $userId);
		$email    	= new EnableBanking($user);
		$email->filterMail();

		$address	= SIM\getModuleOption(MODULE_SLUG, 'email');
		
		wp_mail( $address, $email->subject, $email->message);
	}elseif(empty($formResults['online_statements']) && $enabled){
		$user		= get_user_by('ID', $userId);
		$email    	= new DisableBanking($user);
		$email->filterMail();
		
		$address	= SIM\getModuleOption(MODULE_SLUG, 'email');
		
		wp_mail( $address, $email->subject, $email->message);
	}
	
	return $formResults;
}, 10, 3);