<?php
//© 2019 Martin Peter Madsen
namespace MTM\Utilities\Tools\Bases;

class Convert
{
	//convert any size number from any base to any base
	protected $_bcMath=true;
	
	public function __construct()
	{
		if (extension_loaded("bcmath") === false) {
			$this->_bcMath	= false;
		}
	}
	public function hexToBase36($str)
	{
		if (is_string($str) === false) {
			throw new \Exception("Input must be a string");
		}
		$str	= strtoupper($str);
		if (preg_match("/^([A-F0-9]+)$/", $str) == 0) {
			throw new \Exception("Invalid chars in input");
		}
		return $this->anyToAny($str, "0123456789ABCDEF", "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ");
	}
	public function base36ToHex($str)
	{
		if (is_string($str) === false) {
			throw new \Exception("Input must be a string");
		}
		$str	= strtoupper($str);
		if (preg_match("/^([A-Z0-9]+)$/", $str) == 0) {
			throw new \Exception("Invalid chars in input");
		}
		return $this->anyToAny($str, "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ", "0123456789ABCDEF");
	}
	public function hexToBin($str)
	{
		if (is_string($str) === false) {
			throw new \Exception("Input must be a string");
		}
		$str	= strtoupper($str);
		if (preg_match("/^([A-F0-9]+)$/", $str) == 0) {
			throw new \Exception("Invalid chars in input");
		}
		return $this->anyToAny($str, "0123456789ABCDEF", "01");
	}
	public function binToHex($str)
	{
		if (is_string($str) === false) {
			throw new \Exception("Input must be a string");
		}
		$str	= strtoupper($str);
		if (preg_match("/^([0-1]+)$/", $str) == 0) {
			throw new \Exception("Invalid chars in input");
		}
		return $this->anyToAny($str, "01", "0123456789ABCDEF");
	}
	public function hexToDec($str)
	{
		if (is_string($str) === false) {
			throw new \Exception("Input must be a string");
		}
		$str	= strtoupper($str);
		if (preg_match("/^([A-F0-9]+)$/", $str) == 0) {
			throw new \Exception("Invalid chars in input");
		}
		return $this->anyToAny($str, "0123456789ABCDEF", "0123456789");
	}
	public function anyToAny($str, $from, $to)
	{
		//src: https://www.php.net/manual/en/function.base-convert
		if ($from == $to){
			return $str;
		} elseif ($this->_bcMath === false) {
			throw new \Exception("PHP extension; bcmath, is not loaded");
		}

		$fromBase	= str_split($from, 1);
		$toBase		= str_split($to, 1);
		$number		= str_split($str, 1);
		$fromLen	= strlen($from);
		$toLen		= strlen($to);
		$numberLen	= strlen($str);
		$retval='';
		if ($to == '0123456789') {
			$retval=0;
			for ($i = 1;$i <= $numberLen; $i++) {
				$retval = bcadd($retval, bcmul(array_search($number[$i-1], $fromBase),bcpow($fromLen,$numberLen-$i)));
			}
			return $retval;
		}
		if ($from != '0123456789') {
			$base10		= $this->anyToAny($str, $from, '0123456789');
		} else {
			$base10 	= $str;
		}
		if ($base10<strlen($to)) {
			return $toBase[$base10];
		}
		while($base10 != '0')
		{
			$retval = $toBase[bcmod($base10,$toLen)].$retval;
			$base10 = bcdiv($base10,$toLen,0);
		}
		return $retval;		
	}
}