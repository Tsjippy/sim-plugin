<?php
namespace SIM;

$defaultModules = [
    'admin'         => 'admin',
    'fileupload'    => 'fileUpload'
];

// Store all modulefolders
$dirs    = scandir(MODULESPATH);
$dirs    = array_merge($dirs, scandir(MODULESPATH.'/__defaults'));
foreach($dirs as $key=>$dir){
    if(substr($dir, 0, 2) == '__' || $dir == '.' || $dir == '..'){
        unset($dirs[$key]);
    }
}

//Sort alphabeticalyy, ignore case
sort($dirs, SORT_STRING | SORT_FLAG_CASE);

$moduleDirs  = [];
foreach($dirs as $dir){
    if(in_array($dir, $defaultModules)){
        $moduleDirs[strtolower($dir)] = "__defaults/$dir";
    }else{
        $moduleDirs[strtolower($dir)] = $dir;
    }
}

//load all libraries
require( __DIR__  . '/includes/lib/vendor/autoload.php');

spl_autoload_register(function ($classname) {
    global $moduleDirs;

    $path       = explode('\\', $classname);

    if($path[0] != 'SIM' || !isset($path[1])){
        return;
    }

    $module     = $moduleDirs[strtolower($path[1])];
    $fileName   = $path[2];

    $modulePath = MODULESPATH."$module/php";

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

$Modules		= get_option('sim_modules', []);
//Make sure the default modules are enabled always
foreach($defaultModules as $module){
    $module = strtolower($module);
    if(!isset($Modules[$module])){
        $Modules[$module]  = [
            'enable'    => 'on'
        ];

        update_option('sim_modules', $Modules);
    }
}
unset($module);

//Load all main files
$files = glob(__DIR__  . '/includes/php/*.php');
$files = array_merge($files, glob(__DIR__  . "/includes/blocks/*.php"));

foreach($Modules as $slug=>$settings){
    if(isset($moduleDirs[$slug])){
        $files = array_merge($files, glob(__DIR__  . "/includes/modules/{$moduleDirs[$slug]}/php/*.php"));
        $files = array_merge($files, glob(__DIR__  . "/includes/modules/{$moduleDirs[$slug]}/blocks/*.php"));
    }
}

foreach ($files as $file) {
    require_once($file);   
}