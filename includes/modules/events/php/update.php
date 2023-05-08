<?php
namespace SIM\EVENTS;
use SIM;

add_action('sim_plugin_update', function(){
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    $schedules = new Schedules();

    maybe_add_column($schedules->tableName, 'diner', "ALTER TABLE $schedules->tableName ADD COLUMN `diner` boolean NOT NULL");

    maybe_add_column($schedules->tableName, 'timeslot_size', "ALTER TABLE $schedules->tableName ADD COLUMN `timeslot_size` mediumint(9) NOT NULL");

    maybe_add_column($schedules->tableName, 'hidenames', "ALTER TABLE $schedules->tableName ADD COLUMN `hidenames` boolean NOT NULL");

    maybe_add_column($schedules->tableName, 'admin_roles', "ALTER TABLE $schedules->tableName ADD COLUMN `admin_roles` varchar(80)");

    maybe_add_column($schedules->tableName, 'view_roles', "ALTER TABLE $schedules->tableName ADD COLUMN `view_roles` varchar(80)");
});