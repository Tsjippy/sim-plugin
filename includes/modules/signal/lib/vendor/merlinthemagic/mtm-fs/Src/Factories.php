<?php
//© 2019 Martin Peter Madsen
namespace MTM\FS;

class Factories
{
	private static $_cStore=array();
	
	//USE: $aFact		= \MTM\FS\Factories::$METHOD_NAME();
	
	public static function getFiles()
	{
		if (array_key_exists(__FUNCTION__, self::$_cStore) === false) {
			self::$_cStore[__FUNCTION__]	= new \MTM\FS\Factories\Files();
		}
		return self::$_cStore[__FUNCTION__];
	}
	public static function getDirectories()
	{
		if (array_key_exists(__FUNCTION__, self::$_cStore) === false) {
			self::$_cStore[__FUNCTION__]	= new \MTM\FS\Factories\Directories();
		}
		return self::$_cStore[__FUNCTION__];
	}
}