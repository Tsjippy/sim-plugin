<?php
namespace SIM\PRAYER;
use SIM;

add_filter('sim_personal_signal_settings', function($settings, $user, $prefs){
    $prayerTime = '';
    if(isset($prefs['prayertime'])){
        $prayerTime = $prefs['prayertime'];
    }

    $settings   .= "<label>";
        $settings   .= "<h4>Send me a personal prayer request reminder around:</h4>";
        $settings   .= "<input type='time' name='prayertime' value='$prayerTime'>";
    $settings   .= "</label>";

    return $settings;
}, 10, 3);

add_action('sim_signal_before_pref_save', function($userId, $prefs){
    $prayerTimes    = (array)get_option('signal_prayers');
    $time           = $prefs['prayertime'];
    $oldTime        = get_user_meta($userId, 'signal_preferences', true);
    if(isset($oldTime['prayertime'])){
        $oldTime        = $oldTime['prayertime'];
    }

    // nothing changed
    if($time == $oldTime){
        return;
    }

    // Remove the old time
    if(isset($prayerTimes[$oldTime])){
        $key    = array_search($userId, $prayerTimes[$oldTime]);
        unset($prayerTimes[$oldTime][$key]);
    }

    // There is already an user with a prayer schedule for this time
    if(isset($prayerTimes[$time])){
        $prayerTimes[$time][]   = $userId;
    }else{
        $prayerTimes[$time]  = [$userId];
    }

    update_option('signal_prayers', $prayerTimes);

    // Also add to todays schedule if time is later today
    $curTime	= current_time('H:i');
    if($time > $curTime){
        $date			= \Date('y-m-d');
        $schedule		= (array)get_option("prayer_schedule_$date");

        // Remove the old time
        if(isset($schedule[$oldTime])){
            $key    = array_search($userId, $schedule[$oldTime]);
            unset($schedule[$oldTime][$key]);
        }

        // There is already an user with a prayer schedule for this time
        if(isset($schedule[$time])){
            $schedule[$time][]   = $userId;
        }else{
            $schedule[$time]  = [$userId];
        }

        update_option("prayer_schedule_$date", $schedule);
    }

}, 10, 2);