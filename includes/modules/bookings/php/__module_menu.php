<?php
namespace SIM\BOOKINGS;
use SIM;

const MODULE_VERSION		= '7.0.9';
DEFINE(__NAMESPACE__.'\MODULE_SLUG', strtolower(basename(dirname(__DIR__))));

//run on module activation
add_action('sim_module_activated', function($moduleSlug, $options){
	//module slug should be the same as grandparent folder name
	if($moduleSlug != MODULE_SLUG)	{
		return;
	}
}, 10, 2);

add_filter('sim_module_updated', function($newOptions, $moduleSlug, $oldOptions){
	global $wpdb;

	//module slug should be the same as grandparent folder name
	if($moduleSlug != MODULE_SLUG){
		return $newOptions;
	}

	// enable forms and events modules
	if(!SIM\getModuleOption('forms', 'enable')){
		SIM\ADMIN\enableModule('forms');
	}
	if(!SIM\getModuleOption('events', 'enable')){
		SIM\ADMIN\enableModule('events');
	}

	// Create the table
	$bookings	= new Bookings();
	$bookings->createBookingsTable();

	// Add booking manager role
	if(!wp_roles()->is_role( 'bookingmanager' )){
		add_role( 
			'bookingmanager', 
			'Booking manager', 
			get_role( 'contributor' )->capabilities
		);
	}

	// Add columns to forms table
	$forms	= new SIM\FORMS\SimForms();
	$row = $wpdb->get_results(  "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
	WHERE table_name = '$forms->elTableName' AND column_name = 'booking_details'"  );

	if(empty($row)){
		$wpdb->query("ALTER TABLE $forms->elTableName ADD booking_details text NOT NULL");
	}

	scheduleTasks();

	return $newOptions;
}, 10, 3);

//run on module deactivation
add_action('sim_module_deactivated', function($moduleSlug, $options){
	//module slug should be the same as grandparent folder name
	if($moduleSlug != MODULE_SLUG)	{
		return;
	}
}, 10, 2);

add_filter('sim_submenu_description', function($description, $moduleSlug, $moduleName){
	//module slug should be the same as the constant
	if($moduleSlug != MODULE_SLUG)	{
		return $description;
	}

	ob_start();
	?>
	<p>
		This module is dependend on the events and forms module.<br>
		It adds the possibility to book something thrue a form.<br>
		It will display a calendar showing available dates
	</p>

	<?php

	return ob_get_clean();
}, 10, 3);

add_filter('sim_submenu_options', function($optionsHtml, $moduleSlug, $settings, $moduleName){
	//module slug should be the same as grandparent folder name
	if($moduleSlug != MODULE_SLUG){
		return $optionsHtml;
	}

	ob_start();
	
    ?>

	<?php

	return ob_get_clean();
}, 10, 4);

add_filter('sim_module_data', function($dataHtml, $moduleSlug, $settings){
	//module slug should be the same as grandparent folder name
	if($moduleSlug != MODULE_SLUG){
		return $dataHtml;
	}

	return $dataHtml;
}, 10, 3);