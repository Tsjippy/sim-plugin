<?php
namespace SIM\FORMS;
use SIM;

add_action('sim_plugin_update', function($oldVersion){
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    require_once ABSPATH . 'wp-admin/install-helper.php';

    $simForms = new SimForms();

    if($oldVersion < '2.35.7'){

        maybe_add_column($simForms->elTableName, 'editimage', "ALTER TABLE $simForms->elTableName ADD COLUMN `editimage` boolean NOT NULL");
    }

    if($oldVersion <= '2.42.8'){
        $simForms = new SaveFormSettings();
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
    }

    if($oldVersion <= '2.43.0'){
        maybe_drop_column($simForms->tableName, 'settings', "ALTER TABLE $simForms->tableName DROP COLUMN `settings`");
    }
});