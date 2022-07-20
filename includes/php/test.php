<?php
namespace SIM;

use SIM\FORMS\Formbuilder;
use SMTPValidateEmail\Validator as SmtpEmailValidator;

//Shortcode for testing
add_shortcode("test",function ($atts){
	global $Modules;

	$users = get_users(array(
		'meta_key'     => 'account_statements',
	));

	$base = wp_get_upload_dir()["basedir"]."/private/account_statements/";

	foreach($users as $user){
		$years	= ['2020','2021','2022'];
		$months	= ['01','02','03','04','05','06','07','08','09','10','11','12'];

		$statements	= [];

		foreach($years as $year){
			foreach($months as $month){
				$fileName	= "{$user->user_login}-$year-$month-Account-Statement.rtf";
				if(file_exists($base.$fileName)){
					if(!isset($statements[$year])){
						$statements[$year]	= [];
					}
					if(!isset($statements[$year][$month])){
						$statements[$year][$month]	= [];
					}
					$statements[$year][$month]	= $fileName;
				}
			}
		}

		$partnerId	= hasPartner($user->ID);
		if(empty($statements)){
			if($partnerId){
				$partnerST	= get_user_meta($partnerId, "account_statements", true);

				if(!empty($partnerST)){
					update_user_meta($user->ID, "account_statements", $partnerST);
					continue;
				}
			}
			delete_user_meta($user->ID, "account_statements");
		}else{
			update_user_meta($user->ID, "account_statements", $statements);
			if($partnerId){
				update_user_meta($partnerId, "account_statements", $statements);
			}
		}
		
	}

	return '';
});