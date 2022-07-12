<?php
namespace SIM\ADMIN;
use SIM;

/**
 * Removes unnescearry content from a string
 * 
 * @param   string  $content    Reference to a string
 */
function deslash( &$content ) {
    $content = preg_replace( "/\\\+'/", "'", $content );
    $content = preg_replace( '/\\\+"/', '"', $content );
    $content = preg_replace( '/https?:\/\/https?:\/\//i', 'https://', $content );
}

/**
 * Saves modules settings from $_POST
 */
function saveSettings(){
	global $Modules;

    $moduleSlug	    = $_POST['module'];
    $options		= $_POST;
    unset($options['module']);

    foreach($options as &$option){
        deslash($option);
    }

    // Add e-mail settings
    if(isset($Modules[$moduleSlug]['emails'])){
        $options['emails']  = $Modules[$moduleSlug]['emails'];
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
            enableModule($moduleSlug, $options);
        }
    }

    update_option('sim_modules', $Modules);
}

function saveEmails(){
	global $Modules;

    $moduleSlug	    = $_POST['module'];
    $options		= $_POST['emails'];
    unset($options['module']);

    foreach($options as &$option){
        deslash($option);
    }

    $Modules[$moduleSlug]['emails']	= $options;

    update_option('sim_modules', $Modules);
}

function enableModule($slug, $options){
    global $Modules;
    // Load module files as they might contain activation actions
    $files = glob(__DIR__  . "/../../modules/$slug/php/*.php");
    foreach ($files as $file) {
        require_once($file);   
    }	
    do_action('sim_module_activated', $slug, $options);
    $Modules[$slug]	= apply_filters('sim_module_updated', $options, $slug, $Modules[$slug]);

    update_option('sim_modules', $Modules);
}