<?php
namespace SIM\FORMS;
use SIM;

add_action('sim_plugin_update', function($oldVersion){
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    require_once ABSPATH . 'wp-admin/install-helper.php';

    $simForms = new SimForms();

    if($oldVersion < '2.44.6'){

        maybe_add_column($simForms->tableName, 'reminder_frequency', "ALTER TABLE $simForms->tableName ADD COLUMN `reminder_frequency` text");
        maybe_add_column($simForms->tableName, 'reminder_period', "ALTER TABLE $simForms->tableName ADD COLUMN `reminder_period` text");
        maybe_add_column($simForms->tableName, 'reminder_startdate', "ALTER TABLE $simForms->tableName ADD COLUMN `reminder_startdate` text");

        maybe_drop_column($simForms->tableName, 'settings', "ALTER TABLE $simForms->tableName DROP COLUMN `settings`");
    }
});