<?php
namespace SIM\ADMIN;
use SIM;

function deslash( &$content ) {
    $content = preg_replace( "/\\\+'/", "'", $content );
    $content = preg_replace( '/\\\+"/', '"', $content );
    $content = preg_replace( '/https?:\/\/https?:\/\//i', 'https://', $content );
}

function save_settings(){
	global $Modules;

    $moduleSlug	= $_POST['module'];
    $options		= $_POST;
    unset($options['module']);

    foreach($options as &$option){
        deslash($option);
    }

    //module was already activated
    if(isset($Modules[$moduleSlug])){
        //deactivate the module
        if(!isset($options['enable'])){
            unset($Modules[$moduleSlug]);
            do_action('sim_module_deactivated', $moduleSlug, $options);
        }elseif(!empty($options)){
            $Modules[$moduleSlug]	= apply_filters('sim_module_updated', $options, $moduleSlug, $Modules[$moduleSlug]);
        }
    //module needs to be activated
    }else{
        if(!empty($options)){
            // Load module files as they might contain activation actions
            $files = glob(__DIR__  . "/../../modules/$moduleSlug/php/*.php");
            foreach ($files as $file) {
                require_once($file);   
            }	
            do_action('sim_module_activated', $moduleSlug, $options);
            $Modules[$moduleSlug]	= apply_filters('sim_module_updated', $options, $moduleSlug, $Modules[$moduleSlug]);
        }
    }

    update_option('sim_modules', $Modules);
}