<?php
//© 2019 Martin Madsen
namespace MTM\FS\Factories;

class Base
{
	protected $_cStore=array();
	
	public function getSplitPath($path)
	{
		if (is_string($path) === true) {
			$parts	= array_values(array_filter(explode(DIRECTORY_SEPARATOR, trim($path))));
		} else {
			throw new \Exception("Invalid Input");
		}
		
		return $parts;
	}
}