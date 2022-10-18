<?php
//© 2019 Martin Peter Madsen
if (defined("MTM_FS_BASE_PATH") === false) {
	define("MTM_FS_BASE_PATH", __DIR__ . DIRECTORY_SEPARATOR);
	spl_autoload_register(function($className)
	{
		if (class_exists($className) === false) {
			$cPath		= array_values(array_filter(explode("\\", $className)));
			if (array_shift($cPath) == "MTM") {
				if (array_shift($cPath) == "FS") {
					$filePath	= MTM_FS_BASE_PATH . implode(DIRECTORY_SEPARATOR, $cPath) . ".php";
					if (is_readable($filePath) === true) {
						require_once $filePath;
					}
				}
			}
		}
	});
	function loadMtmFs()
	{
		if (defined("MTM_FS_TEMP_PATH") === false) {
			
			if (strpos(strtolower(php_uname()), "linux ") === 0) {
				//we really prefer ram backed storage for the temp files
				$memPath	= DIRECTORY_SEPARATOR . "dev" . DIRECTORY_SEPARATOR . "shm" . DIRECTORY_SEPARATOR;
				if (is_writable($memPath) === true) {
					define("MTM_FS_TEMP_PATH", $memPath);
				} else {
					
					//FHS mandated world writable: /var/tmp
					//https://www.pathname.com/fhs/pub/fhs-2.3.html 
					$tmpPath	= DIRECTORY_SEPARATOR . "var" . DIRECTORY_SEPARATOR . "tmp" . DIRECTORY_SEPARATOR;
					if (is_writable($tmpPath) === true) {
						//use this over sys_get_temp_dir() on linux as e.g. httpd will overide the std /tmp/ with a private version
						define("MTM_FS_TEMP_PATH", $tmpPath);
					}
				}
			}
			if (defined("MTM_FS_TEMP_PATH") === false) {
				//use the standard temp path
				define("MTM_FS_TEMP_PATH", sys_get_temp_dir() . DIRECTORY_SEPARATOR);
			}
		}
	}
	loadMtmFs();
}