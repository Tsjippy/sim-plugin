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

    $module_slug	= $_POST['module'];
    $options		= $_POST;
    unset($options['module']);

    foreach($options as &$option){
        deslash($option);
    }

    //module was already activated
    if(isset($Modules[$module_slug])){
        //deactivate the module
        if(!isset($options['enable'])){
            unset($Modules[$module_slug]);
            do_action('sim_module_deactivated', $module_slug, $options);
        }elseif(!empty($options)){
            $Modules[$module_slug]	= $options;
            do_action('sim_module_updated', $module_slug, $options);
        }
    //module needs to be activated
    }else{
        if(!empty($options)){
            $Modules[$module_slug]	= $options;

            // Load module files as they might contain activation actions
            $files = glob(__DIR__  . "/../../modules/$module_slug/php/*.php");
            foreach ($files as $file) {
                require_once($file);   
            }	
            do_action('sim_module_activated', $module_slug, $options);
            do_action('sim_module_updated', $module_slug, $options);
        }
    }

    update_option('sim_modules', $Modules);
}