<?php
namespace SIM;

use SIM\FORMS\Formbuilder;
use SMTPValidateEmail\Validator as SmtpEmailValidator;

//Shortcode for testing
add_shortcode("test",function ($atts){
	global $Modules;

	unset($Modules['extra_post_types']);
	unset($Modules['template_specific']);
	unset($Modules['pdf']);
	unset($Modules['celebrations']);
	unset($Modules['schedules']);

	foreach(['frontend_posting',
	'user_management',
	'user_pages',
	'SIM Nigeria',
	'PDF',
	'default_pictures',
	'mail_posting',
	'fancy_email',
	'content_filter',
	'media_gallery',
	'embed_page'] as $key){
		if(isset($Modules[$key])){
			$newkey	= str_replace(['_',' '], '', strtolower($key));

			$Modules[$newkey]	= $Modules[$key];
			unset($Modules[$key]);
		}
	}

	if(isset($Modules['bulk_meta_update'])){
		$Modules['bulkchange']	= $Modules['bulk_meta_update'];
		unset($Modules['bulk_meta_update']);
	}

	if(isset($Modules['mandatory_content'])){
		$Modules['mandatory']	= $Modules['mandatory_content'];
		unset($Modules['mandatory_content']);
	}

	if(!is_array($Modules['frontendposting']["front_end_post_pages"])){
		$Modules['frontendposting']["front_end_post_pages"] = (array)$Modules['frontendposting']["publish_post_page"];
		unset($Modules['frontendposting']["publish_post_page"]);
	}

	if(!is_array($Modules['frontpage']["home_page"])){
		$Modules['frontpage']["home_page"] = (array)$Modules['frontpage']["home_page"];
	}

	if(!is_array($Modules['login']["password_reset_page"])){
		$Modules['login']["password_reset_page"] = (array)$Modules['login']["password_reset_page"];
	}

	if(!is_array($Modules['login']["register_page"])){
		$Modules['login']["register_page"] = (array)$Modules['login']["register_page"];
	}

	if(!is_array($Modules['login']["2fa_page"])){
		$Modules['login']["2fa_page"] = (array)$Modules['login']["2fa_page"];
	}

	$Modules['userpages']["allcontacts_pages"] = [204];

	update_option('sim_modules', $Modules);

	return '';
});