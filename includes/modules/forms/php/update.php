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
});