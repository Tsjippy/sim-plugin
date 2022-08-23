<?php
namespace SIM;

//Shortcode for testing
add_shortcode("test",function ($atts){
	global $Modules;

	PRAYER\sendPrayerRequests();

	return 'e-mail send';
});