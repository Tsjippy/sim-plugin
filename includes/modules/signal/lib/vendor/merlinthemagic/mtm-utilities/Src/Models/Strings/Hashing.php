<?php
//© 2019 Martin Peter Madsen
namespace MTM\Utilities\Models\Strings;

class Hashing
{
	protected $_signIntLen=null;
	
	public function __construct()
	{
		$this->_signIntLen	= strlen((string) PHP_INT_MAX);
	}
	public function getSha256($str)
	{
		if (is_string($str) === false) {
			throw new \Exception("Invalid Input");
		}
		return hash("sha256", $str);
	}
	public function getSha512($str)
	{
		if (is_string($str) === false) {
			throw new \Exception("Invalid Input");
		}
		return hash("sha512", $str);
	}
	public function getAsInteger($str, $max=PHP_INT_MAX)
	{
		//the best result we can hope for is sqrt($max) before a duplicate is returned
		//2147483647 ~ 46340 | 4294967295 ~ 65535
		//convert a string into a "unique" hash consisting only of integers
		//that is less than or equal to the max
		if (is_int($max) === false || $max > PHP_INT_MAX) {
			//enforce max or the compare below will have undefined behavior
			throw new \Exception("Max value must be an int and cannot exceed MAX INT");
		}
		$val	= trim(preg_replace("/([^0-9]+)/", "", $this->getSha512($str)), "0");
		if ($val == "") {
			$val	= "0"; //no digits in hash
		}
		$rVal	= "";
		foreach (str_split($val, 1) as $digit) {
			$nVal	= $rVal . $digit;
			if ($nVal < $max) {
				$rVal	= $nVal;
			} else {
				break;
			}
		}
		return intval($rVal);
	}
	public function getAsIntegerV2($str, $max=PHP_INT_MAX)
	{
		//slightly better randomness than v1 but also slower
		//the best result we can hope for is sqrt($max) before a duplicate is returned
		//2147483647 ~ 46340 | 4294967295 ~ 65535
		//convert a string into a "unique" hash consisting only of integers
		//that is less than or equal to the max.
		if (is_int($max) === false || $max > PHP_INT_MAX) {
			//enforce max or the compare below will have undefined behavior
			throw new \Exception("Max value must be an int and cannot exceed MAX INT");
		}
		//use the $max as part of the input string, this removes the coupling when an input hashes to < $max
		//without it the input would return the same for ($max * 2), because that is still less than $max and mod will be a pure reminder
		$intStr		= substr(\MTM\Utilities\Factories::getBases()->getConvert()->hexToDec($this->getSha256($str.$max)), 1, $this->_signIntLen);
		if (intval($intStr) === PHP_INT_MAX && $intStr !== (string) PHP_INT_MAX) {
			//too large, shave off a full digit
			$intStr		= substr($intStr, 1);
		}
		return intval(intval($intStr) % $max);
	}
}