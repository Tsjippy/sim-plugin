<?php
//© 2019 Martin Peter Madsen
namespace MTM\Utilities\Models\Strings;

class Random
{
	public function getByRegex($len, $regex="A-Za-z0-9\#\-\+\:\;\.\,\!")
	{
		$aChars		= "ABCDEFGHKLMNOPQRSTUVWXYZabcdefghklmnopqrstuvwxyz0123456789!@#$%^&*()_-+=(){}[]:;,.<>?";
		$sChars		= preg_replace("/[^".$regex."]/", "", $aChars);
		$sLen		= strlen($sChars);
		if ($sLen > 0) {
			$chars		= str_split($sChars, 1);
			$str		= "";
			for ($x=0; $x < $len; $x++) {
				$str	.= $chars[random_int(0, PHP_INT_MAX) % $sLen];
			}
			return $str;
			
		} else {
			throw new \Exception("Invalid regex");
		}
	}
}