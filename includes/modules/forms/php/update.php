<?php
namespace SIM\FORMS;
use SIM;

add_action('sim_plugin_update', function(){
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    $simForms = new SimForms();

    maybe_add_column($simForms->elTableName, 'editimage', "ALTER TABLE $simForms->elTableName ADD COLUMN `editimage` boolean NOT NULL");
});