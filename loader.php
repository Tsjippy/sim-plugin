<?php
namespace SIM;

/**
 * Gets the module name based on the slug
 *
 * @param   string  $slug       string containing the raw name
 * @param   string  $seperator  the symbol to use in between words. Default ''
 * 
 * @return  string              the name
 */
function getModuleName($slug, $seperator=''){
    // get the module name from the path
	$slug		= basename($slug);
	$pieces 	= preg_split('/(?=[A-Z])/', ucwords($slug));
    $name       = trim(implode($seperator, $pieces));

    if($seperator == ''){
        $name   = strtolower($name);
    }
	return $name;
}

// Class loader function
spl_autoload_register(function ($classname) {
    loadModules();

    global $moduleDirs;
    global $Modules;

    $path       = explode('\\', $classname);

    if($path[0] != 'SIM' || !isset($path[1])){
        return;
    }

    $moduleDir  = $moduleDirs[strtolower($path[1])];
    $moduleName = getModuleName($moduleDir);

    if(!isset($Modules[$moduleName]) && (empty($_GET['page']) || $_GET['page'] != "sim_$moduleName")){
        return; // module is not activated
    }

    $fileName   = $path[2];

    $modulePath = "$moduleDir/php";

	$classFile	= "$modulePath/classes/$fileName.php";
    $traitFile	= "$modulePath/traits/$fileName.php";
	if(file_exists($classFile)){
		require_once($classFile);
	}elseif(file_exists($traitFile)){
		require_once($traitFile);
	}else{
        printArray($classFile.' not found');
    }
});

function loadModules(){
    global $moduleDirs;
    global $defaultModules;
    global $Modules;

    if(!empty($moduleDirs)){
        return;// only run once
    }

    // Store all modulefolders
    $moduleDirs     = [];
    $defaultModules = [];

    // default modules
    foreach(scandir(INCLUDESPATH.'default_modules') as $dir){
        if(substr($dir, 0, 2) == '__' || $dir == '.' || $dir == '..' || !is_dir(INCLUDESPATH."default_modules/$dir")){
            continue;
        }

        $moduleDirs[strtolower($dir)]   = INCLUDESPATH."default_modules/$dir";

        $moduleName         = getModuleName($dir);
        $defaultModules[]   = $moduleName;
    }

    // normal modules
    foreach(scandir(MODULESPATH) as $key=>$dir){
        if(str_contains($dir, 'node_modules') || $dir == '.' || $dir == '..' || !is_dir(MODULESPATH.$dir)){
            continue;
        }

        $moduleDirs[strtolower($dir)] = MODULESPATH.$dir;
    }

    $moduleDirs = apply_filters('sim-moduledirs', $moduleDirs);

    //Sort alphabeticalyy, ignore case
    ksort($moduleDirs, SORT_STRING | SORT_FLAG_CASE);

    //load all libraries
    require( __DIR__  . '/includes/lib/vendor/autoload.php');

    $Modules		= get_option('sim_modules', []);
    ksort($Modules);

    //Make sure the default modules are enabled always
    foreach($defaultModules as $module){
        $module = $module;
        if(!isset($Modules[$module])){
            $Modules[$module]  = [
                'enable'    => 'on'
            ];

            update_option('sim_modules', $Modules);
        }
    }
    unset($module);
}

loadModules();

global $moduleDirs;
global $Modules;

//Load all main files
$files = glob(__DIR__  . '/includes/php/*.php');
$files = array_merge($files, glob(__DIR__  . "/includes/blocks/*.php"));

foreach($Modules as $slug=>$settings){
    if(isset($moduleDirs[$slug]) && !empty($settings['enable'])){
        $files = array_merge($files, glob("{$moduleDirs[$slug]}/php/*.php"));
        $files = array_merge($files, glob("{$moduleDirs[$slug]}/blocks/*.php"));
    }
}

foreach ($files as $file) {
    $result = require_once($file);

    if(is_wp_error($result)){
        echo "<div class='error' style='background-color:white;'>".$result->get_error_message()."</div>";
    }
}