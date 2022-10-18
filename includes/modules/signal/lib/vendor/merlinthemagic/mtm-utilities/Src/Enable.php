<?php
//© 2019 Martin Peter Madsen
if (defined("MTM_UTILITIES_BASE_PATH") === false) {
	define("MTM_UTILITIES_BASE_PATH", __DIR__ . DIRECTORY_SEPARATOR);
	spl_autoload_register(function($className)
	{
		if (class_exists($className) === false) {
			$cPath		= array_values(array_filter(explode("\\", $className)));
			if (array_shift($cPath) == "MTM") {
				if (array_shift($cPath) == "Utilities") {
					$filePath	= MTM_UTILITIES_BASE_PATH . implode(DIRECTORY_SEPARATOR, $cPath) . ".php";
					if (is_readable($filePath) === true) {
						require_once $filePath;
					}
				}
			}
		}
	});
	if (defined("MTM_LOADED_TIME") === false) {
		define("MTM_LOADED_TIME", \MTM\Utilities\Factories::getTime()->getMicroEpoch());
	}
	function mtmUtilitiesShutdown()
	{
		if (defined("MTM_SHUTDOWN") === false) {
			define("MTM_SHUTDOWN", true);
		}
	}
	register_shutdown_function("mtmUtilitiesShutdown");
}