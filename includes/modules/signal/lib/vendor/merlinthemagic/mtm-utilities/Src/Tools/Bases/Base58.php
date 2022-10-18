<?php
//© 2019 Martin Peter Madsen
namespace MTM\Utilities\Tools\Bases;

class Base58
{
	public function hexToBTC($str)
	{
		//The Base58 symbol chart used in Bitcoin is specific to the Bitcoin project and is not intended 
		//to be the same as any other Base58 implementation used outside the context of Bitcoin (the characters excluded are: 0, O, I, and l). 
		//src: https://en.bitcoin.it/wiki/Base58Check_encoding
		
		if (is_string($str) === false) {
			throw new \Exception("Input must be a string");
		}
		$str	= strtoupper($str);
		if (preg_match("/^([A-F0-9]+)$/", $str) == 0) {
			throw new \Exception("Invalid chars in input");
		}
		$toolObj	= \MTM\Utilities\Factories::getBases()->getConvert();
		return $toolObj->anyToAny($str, "0123456789ABCDEF", "123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz");
	}
	
}