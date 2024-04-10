<?php
namespace SIM;

use Exception;
use mikehaertl\shellcommand\Command;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpWord\IOFactory;

//Shortcode for testing
add_shortcode("test", function ($atts){
    global $wpdb;
    global $Modules;

	FORMS\getAllEmptyRequiredElements(45, 'mandatory');

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';

	$simForms = new FORMS\SaveFormSettings();
	$simForms->getForms();

    maybe_add_column($simForms->tableName, 'button_text', "ALTER TABLE $simForms->tableName ADD COLUMN `button_text` text");
	maybe_add_column($simForms->tableName, 'succes_message', "ALTER TABLE $simForms->tableName ADD COLUMN `succes_message` text ");
	maybe_add_column($simForms->tableName, 'include_id', "ALTER TABLE $simForms->tableName ADD COLUMN `include_id` boolean");
	maybe_add_column($simForms->tableName, 'form_name', "ALTER TABLE $simForms->tableName ADD COLUMN `form_name` text");
	maybe_add_column($simForms->tableName, 'save_in_meta', "ALTER TABLE $simForms->tableName ADD COLUMN `save_in_meta` boolean");
	maybe_add_column($simForms->tableName, 'form_url', "ALTER TABLE $simForms->tableName ADD COLUMN `form_url` text");
	maybe_add_column($simForms->tableName, 'form_reset', "ALTER TABLE $simForms->tableName ADD COLUMN `form_reset` boolean");
	maybe_add_column($simForms->tableName, 'actions', "ALTER TABLE $simForms->tableName ADD COLUMN `actions` text");
	maybe_add_column($simForms->tableName, 'autoarchive', "ALTER TABLE $simForms->tableName ADD COLUMN `autoarchive` boolean");
	maybe_add_column($simForms->tableName, 'autoarchive_el', "ALTER TABLE $simForms->tableName ADD COLUMN `autoarchive_el` integer");
	maybe_add_column($simForms->tableName, 'autoarchive_value', "ALTER TABLE $simForms->tableName ADD COLUMN `autoarchive_value` text");
	maybe_add_column($simForms->tableName, 'split', "ALTER TABLE $simForms->tableName ADD COLUMN `split` text");
	maybe_add_column($simForms->tableName, 'full_right_roles', "ALTER TABLE $simForms->tableName ADD COLUMN `full_right_roles` text");
	maybe_add_column($simForms->tableName, 'submit_others_form', "ALTER TABLE $simForms->tableName ADD COLUMN `submit_others_form` text");
	maybe_add_column($simForms->tableName, 'upload_path', "ALTER TABLE $simForms->tableName ADD COLUMN `upload_path` text");

	foreach($simForms->forms as $form){
 		$settings	= unserialize($form->settings);

		$settings['button_text']		= $settings['buttontext'];
		$settings['succes_message']		= $settings['succesmessage'];
		$settings['include_id']			= $settings['includeid'] == 'includeid' ? true : false;
		$settings['save_in_meta']		= $settings['save_in_meta'] == 'save_in_meta' ? true : false;
		$settings['form_reset']			= $settings['formreset'] == 'formreset' ? true : false;
		$settings['autoarchive']		= $settings['autoarchive'] == 'true' ? true : false;
		$settings['form_name']			= $settings['formname'];
		$settings['form_url']			= $settings['formurl'];
		$settings['autoarchive_el']		= $settings['autoarchivefield'];
		$settings['autoarchive_value']	= $settings['autoarchivevalue'];

		
		$simForms->updateFormSettings($form->id, $settings);
	}
	
    /* $posts = get_posts(
		array(
			'post_type'		=> 'any',
			//'author'		=> 137,
			'numberposts'	=> -1,
		)
	);

    foreach($posts as $post){
       
    }  */
});

// turn off incorrect error on localhost
add_filter( 'wp_mail_smtp_core_wp_mail_function_incorrect_location_notice', '__return_false' );