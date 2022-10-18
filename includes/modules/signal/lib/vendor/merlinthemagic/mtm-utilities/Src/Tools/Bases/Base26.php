<?php
//© 2019 Martin Peter Madsen
namespace MTM\Utilities\Tools\Bases;

class Base26
{
	protected $_values=array("A" => 1, "B" => 2, "C" => 3, "D" => 4, "E" => 5, "F" => 6, "G" => 7, "H" => 8, "I" => 9, "J" => 10, "K" => 11, "L" => 12, "M" => 13, "N" => 14, "O" => 15, "P" => 16, "Q" => 17, "R" => 18, "S" => 19, "T" => 20, "U" => 21, "V" => 22, "W" => 23, "X" => 24, "Y" => 25, "Z" => 26);
	
	public function getAsInt($str, $sigRight=false)
	{
		$int	= 0;
		$chars	= str_split(strtoupper($str));
		if ($sigRight === false) {
			//Most significant is the left most letter
			//Example: A = 0, Z=25, AA = 26, ZA = 676, AB = 27
			$c	= count($chars) - 1;
			foreach ($chars as $cpos => $char) {
				if (array_key_exists($char, $this->_values) === true) {
					$int	+= $this->_values[$char] * pow(26, ($c - $cpos));
				} else {
					throw new \Exception("Invalid char: " . $char);
				}
			}
		} else {
			//Most significant is the right most letter
			//Example: A = 0, Z=25, AA = 26, ZA = 51, AB = 52
			foreach ($chars as $cpos => $char) {
				if (array_key_exists($char, $this->_values) === true) {
					$int	+= $this->_values[$char] * pow(26, $cpos);
				} else {
					throw new \Exception("Invalid char: " . $char);
				}
			}
		}
		return $int - 1;
	}
}