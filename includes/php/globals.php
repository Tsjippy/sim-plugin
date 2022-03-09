<?php
namespace SIM;
define('SITEURL',get_site_url());
define('INCLUDESURL', plugins_url('',__DIR__));
define('PICTURESURL', INCLUDESURL.'/pictures');
define('LOADERIMAGEURL', PICTURESURL.'/loading.gif');

//ALl global available variables
$CustomSimSettings 		= get_option("customsimsettings");

if($CustomSimSettings == false){
	echo "Please set the options in /wp-admin/options-general.php?page=custom_simnigeria";
}else{
	//Global variables
	$ScheduledFunctions = [
		['recurrence'=>'monthly','hookname'=>'check_last_login_date'],
		['recurrence'=>'daily','hookname'=>'process_images'],
		['recurrence'=>'monthly','hookname'=>'page_age_warning'],
		['recurrence'=>'threemonthly','hookname'=>'send_missonary_detail']
	];
}