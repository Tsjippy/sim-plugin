<?php
namespace SIM\FORMS;
use SIM;

add_action('sim_plugin_update', function($oldVersion){
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    require_once ABSPATH . 'wp-admin/install-helper.php';

    $simForms = new SimForms();

    if($oldVersion < '2.44.6'){

        maybe_add_column($simForms->tableName, 'reminder_frequency', "ALTER TABLE $simForms->tableName ADD COLUMN `reminder_frequency` LONGTEXT");
        maybe_add_column($simForms->tableName, 'reminder_period', "ALTER TABLE $simForms->tableName ADD COLUMN `reminder_period` LONGTEXT");
        maybe_add_column($simForms->tableName, 'reminder_startdate', "ALTER TABLE $simForms->tableName ADD COLUMN `reminder_startdate` LONGTEXT");

        maybe_drop_column($simForms->tableName, 'settings', "ALTER TABLE $simForms->tableName DROP COLUMN `settings`");
    }

    if($oldVersion < '2.45.0'){

        maybe_add_column($simForms->tableName, 'reminder_conditions', "ALTER TABLE $simForms->tableName ADD COLUMN `reminder_conditions` LONGTEXT");
    }

    if($oldVersion < '2.46.0'){

        maybe_add_column($simForms->tableName, 'button_text', "ALTER TABLE $simForms->tableName ADD COLUMN `button_text` LONGTEXT");
        maybe_add_column($simForms->tableName, 'succes_message', "ALTER TABLE $simForms->tableName ADD COLUMN `succes_message` LONGTEXT");
        maybe_add_column($simForms->tableName, 'include_id', "ALTER TABLE $simForms->tableName ADD COLUMN `include_id` LONGTEXT");
        maybe_add_column($simForms->tableName, 'form_name', "ALTER TABLE $simForms->tableName ADD COLUMN `form_name` LONGTEXT");
        maybe_add_column($simForms->tableName, 'save_in_meta', "ALTER TABLE $simForms->tableName ADD COLUMN `save_in_meta` LONGTEXT");
        maybe_add_column($simForms->tableName, 'form_url', "ALTER TABLE $simForms->tableName ADD COLUMN `form_url` LONGTEXT");
        maybe_add_column($simForms->tableName, 'form_reset', "ALTER TABLE $simForms->tableName ADD COLUMN `form_reset` LONGTEXT");
        maybe_add_column($simForms->tableName, 'actions', "ALTER TABLE $simForms->tableName ADD COLUMN `actions` LONGTEXT");
        maybe_add_column($simForms->tableName, 'autoarchive', "ALTER TABLE $simForms->tableName ADD COLUMN `autoarchive` LONGTEXT");
        maybe_add_column($simForms->tableName, 'autoarchive_el', "ALTER TABLE $simForms->tableName ADD COLUMN `autoarchive_el` LONGTEXT");
        maybe_add_column($simForms->tableName, 'autoarchive_value', "ALTER TABLE $simForms->tableName ADD COLUMN `autoarchive_value` LONGTEXT");
        maybe_add_column($simForms->tableName, 'split', "ALTER TABLE $simForms->tableName ADD COLUMN `split` LONGTEXT");
        maybe_add_column($simForms->tableName, 'full_right_roles', "ALTER TABLE $simForms->tableName ADD COLUMN `full_right_roles` LONGTEXT");
        maybe_add_column($simForms->tableName, 'submit_others_form', "ALTER TABLE $simForms->tableName ADD COLUMN `submit_others_form` LONGTEXT");
        maybe_add_column($simForms->tableName, 'upload_path', "ALTER TABLE $simForms->tableName ADD COLUMN `upload_path` LONGTEXT");
        maybe_add_column($simForms->tableName, 'reminder_frequency', "ALTER TABLE $simForms->tableName ADD COLUMN `reminder_frequency` LONGTEXT");
        maybe_add_column($simForms->tableName, 'text', "ALTER TABLE $simForms->tableName ADD COLUMN `text` LONGTEXT");
        maybe_add_column($simForms->tableName, 'reminder_period', "ALTER TABLE $simForms->tableName ADD COLUMN `reminder_period` LONGTEXT");
        maybe_add_column($simForms->tableName, 'reminder_startdate', "ALTER TABLE $simForms->tableName ADD COLUMN `reminder_startdate` LONGTEXT");
        maybe_add_column($simForms->tableName, 'reminder_conditions', "ALTER TABLE $simForms->tableName ADD COLUMN `reminder_conditions` LONGTEXT");    	

        foreach($simForms->forms as $form){
            $settings   = maybe_unserialize($form->settings);

            $wpdb->update(
                $simForms->tableName,
                $settings,
                array(
                    'id'		=> $form->id
                ),
            );
        }
        maybe_drop_column($simForms->tableName, 'settings', "ALTER TABLE $simForms->tableName DROP COLUMN `settings`");
    }
});