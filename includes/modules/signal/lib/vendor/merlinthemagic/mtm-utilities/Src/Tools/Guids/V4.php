<?php
//© 2019 Martin Peter Madsen
namespace MTM\Utilities\Tools\Guids;

class V4
{
	public function get($inclBraces=true)
	{
		mt_srand((double)microtime()*10000);
		$charid	= strtoupper(md5(uniqid(rand(), true)));
		$hyphen	= chr(45);
		
		$uuid	= "";
		if ($inclBraces === true) {
			$uuid	.= chr(123);
		}

		$uuid	.= substr($charid, 0, 8) . $hyphen . substr($charid, 8, 4) . $hyphen . substr($charid,12, 4);
		$uuid	.= $hyphen . substr($charid,16, 4) . $hyphen . substr($charid,20,12);
		
		if ($inclBraces === true) {
			$uuid	.= chr(125);
		}
		return $uuid;
	}
	public function isValid($str, $inclBraces=true)
	{
		if ($inclBraces === false && is_string($str) === true && preg_match("/^([A-Z0-9]{8})\-([A-Z0-9]{4})\-([A-Z0-9]{4})\-([A-Z0-9]{4})\-([A-Z0-9]{12})$/", $str) == 1) {
	        return true;
		} elseif ($inclBraces === true && is_string($str) === true && preg_match("/^\{([A-Z0-9]{8})\-([A-Z0-9]{4})\-([A-Z0-9]{4})\-([A-Z0-9]{4})\-([A-Z0-9]{12})\}$/", $str) == 1) {
			return true;
		}
	    return false;
	}
}