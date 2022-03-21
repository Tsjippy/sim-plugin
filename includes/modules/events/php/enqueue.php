<?php
namespace SIM\EVENTS;
use SIM;

add_action( 'save_post', function($post_ID, $post){
    if(has_shortcode($post->post_content, 'schedules')){
        global $Modules;

        if(!is_array($Modules['events']['schedule_pages'])){
            $Modules['events']['schedule_pages']    = [$post_ID];
        }else{
            $Modules['events']['schedule_pages'][]  = $post_ID;
        }

        update_option('sim_modules', $Modules);
    }

    if(has_shortcode($post->post_content, 'upcomingevents')){
        global $Modules;

        if(!is_array($Modules['events']['upcomingevents_pages'])){
            $Modules['events']['upcomingevents_pages']    = [$post_ID];
        }else{
            $Modules['events']['upcomingevents_pages'][]  = $post_ID;
        }

        update_option('sim_modules', $Modules);
    }
}, 10, 2);

add_action( 'wp_enqueue_scripts', function(){
    //css
    wp_register_style('sim_schedules_css', plugins_url('css/schedules.min.css', __DIR__), array(), ModuleVersion);
    wp_register_style('sim_events_css', plugins_url('css/events.min.css', __DIR__), array(), ModuleVersion);
        
    //js
    //selectable select table cells https://github.com/Mobius1/Selectable
	wp_register_script('selectable', "https://unpkg.com/selectable.js@latest/selectable.min.js", array(), null, true);

    wp_register_script('sim_schedules_script', plugins_url('js/schedules.min.js', __DIR__), array('sim_table_script','selectable','sim_other_script'), ModuleVersion, true);
    wp_register_script('sim_event_script', plugins_url('js/events.min.js', __DIR__), array('sim_other_script'), ModuleVersion,true);

    $schedule_pages         = SIM\get_module_option('events', 'schedule_pages');
    $upcomingevents_pages   = SIM\get_module_option('events', 'upcomingevents_pages');
    if(in_array(get_the_ID(), $schedule_pages)){
        wp_enqueue_style('sim_schedules_css');

        wp_enqueue_script('sim_schedules_script');
    }elseif(in_array(get_the_ID(), $upcomingevents_pages)){
        wp_enqueue_style('sim_events_css');
    }
});