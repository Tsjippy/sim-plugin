<?php
namespace SIM\EVENTS;
use SIM;

add_action('sim_plugin_update', function($pluginVersion){
    global $wpdb;

    $schedules = new Schedules();

    $wpdb->query(
        "ALTER TABLE $schedules->tableName ADD COLUMN `diner` boolean NOT NULL, ADD COLUMN `timeslot_size` mediumint(9) NOT NULL, ADD COLUMN `hidenames` boolean NOT NULL, ADD COLUMN `admin_roles` varchar(80), ADD COLUMN `view_roles` varchar(80)"
    );
});