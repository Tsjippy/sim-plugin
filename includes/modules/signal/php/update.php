<?php
namespace SIM\SIGNAL;
use SIM;

add_action('sim_plugin_update', function(){
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    $signal 	= new SignalBus();

    maybe_add_column($signal->tableName, 'stamp', "ALTER TABLE $signal->tableName ADD COLUMN `stamp` text");

    maybe_add_column($signal->receivedTableName, 'chat', "ALTER TABLE $signal->receivedTableName ADD COLUMN `chat` longtext");

    if(maybe_drop_column( $signal->receivedTableName, 'groupid', "ALTER TABLE $signal->receivedTableName  DROP `groupid`;" )){
        SIM\printArray("Dropped column groupid");
    }else{
        SIM\printArray("Failed to drop column groupid");
    }
});