<?php
namespace SIM\ADMIN;
use SIM;

/**
 * Saves modules settings from $_POST
 */
function saveSettings(){
	global $Modules;

    if(
		!isset($_POST['module']) ||
		!isset($_POST['nonce']) ||
		!wp_verify_nonce(wp_unslash($_POST['nonce']), 'module-settings' )
	){
		return '';
	}

    $moduleSlug	    = sanitize_key(wp_unslash($_POST['module']));
    $options		= $_POST;
    unset($options['module']);

    foreach($options as &$option){
        $option = SIM\deslash($option);
    }

    // Add e-mail settings
    if(isset($Modules[$moduleSlug]['emails'])){
        $options['emails']  = $Modules[$moduleSlug]['emails'];
    }

    //module was already activated
    if(isset($Modules[$moduleSlug])){

        // Reactivate
        if(isset($options['enable']) && !isset($Modules[$moduleSlug]['enable'])){
            enableModule($moduleSlug, $options);
        //deactivate the module
        }elseif(!isset($options['enable'])){
            unset($Modules[$moduleSlug]['enable']);
            do_action('sim_module_deactivated', $moduleSlug, $options);
        }elseif(!empty($options)){
            $Modules[$moduleSlug]	= apply_filters("sim_module_{$moduleSlug}_after_save", $options, $Modules[$moduleSlug]);
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

    if(
		!isset($_POST['module']) ||
		!isset($_POST['nonce']) ||
		!wp_verify_nonce(wp_unslash($_POST['nonce']), 'module-settings' )
	){
		return '';
	}

    $moduleSlug	    = $_POST['module'];
    $options		= $_POST['emails'];
    unset($options['module']);

    foreach($options as &$option){
        $option = SIM\deslash($option);
    }

    $Modules[$moduleSlug]['emails']	= $options;

    update_option('sim_modules', $Modules);
}

function enableModule($slug, $options=['enable'=>'on']){
    global $Modules;
    global $moduleDirs;

    // Load module files as they might contain activation actions
    $dir    = $moduleDirs[$slug];
    $files  = glob("$dir/php/*.php");
    foreach ($files as $file) {
        require_once($file);
    }

    do_action("sim_module_{$slug}_activated", $options);
    $Modules[$slug]	= apply_filters("sim_module_{$slug}_after_save", $options, $Modules[$slug]);

    update_option('sim_modules', $Modules);
}