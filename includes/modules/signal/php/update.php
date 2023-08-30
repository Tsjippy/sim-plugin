<?php
namespace SIM\SIGNAL;
use SIM;

add_action('sim_plugin_update', function(){
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    $signal 	= new SignalBus();

    maybe_add_column($signal->tableName, 'stamp', "ALTER TABLE $signal->tableName ADD COLUMN `stamp` text");
});