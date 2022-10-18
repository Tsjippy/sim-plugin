<?php
//© 2020 Martin Peter Madsen
namespace MTM\Utilities\Tools\Arrays\Differential;

class OneDimention
{
	public function getAddedIntegers($arr1, $arr2)
	{
		//find integers in arr2 that are not in $arr1
		if (is_array($arr1) === false || is_array($arr2) === false) {
			throw new \Exception("Invalid input");
		}
		$rArr	= array();
		foreach ($arr2 as $val2) {
			if (is_int($val2) === true) {
				$key	= array_search($val2, $arr1, true);
				if ($key === false) {
					$rArr[]	= $val2;
				}
			}
		}
		return $rArr;
	}
	public function getRemovedIntegers($arr1, $arr2)
	{
		//find integers in arr1 that are not in $arr2
		return $this->getAddedIntegers($arr2, $arr1);
	}
	public function getAddedStrings($arr1, $arr2)
	{
		//find strings in arr2 that are not in $arr1
		if (is_array($arr1) === false || is_array($arr2) === false) {
			throw new \Exception("Invalid input");
		}
		$rArr	= array();
		foreach ($arr2 as $val2) {
			if (is_string($val2) === true) {
				$key	= array_search($val2, $arr1, true);
				if ($key === false) {
					$rArr[]	= $val2;
				}
			}
		}
		return $rArr;
	}
	public function getRemovedStrings($arr1, $arr2)
	{
		//find strings in arr1 that are not in $arr2
		return $this->getAddedStrings($arr2, $arr1);
	}
}