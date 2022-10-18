<?php
//Љ 2022 Martin Peter Madsen
namespace MTM\Utilities\Tools\Strings;

class ASCII
{
	protected $_findV1=array("/[Р-Х]/","/Ц/","/Ч/","/[Ш-Ы]/","/[Ь-Я]/","/а/","/б/","/[в-жи]/","/з/","/[й-м]/","/[н-п]/","/[р-х]/","/ц/","/ч/","/[ш-ы]/","/[ь-я]/","/№/","/ё/","/[ђ-іј]/","/ї/","/[љ-ќ]/","/[§-џ]/");
	protected $_replaceV1=array("A","AE","C","E","I","D","N","O","X","U","Y","a","ae","c","e","i","d","n","o","x","u","y");
	
	public function replaceV1($str)
	{
		//converts non-printable to ASCII, without having to install the international package
		if (is_string($str) === false) {
			throw new \Exception("Invalid input");
		}
		//src: https://stackoverflow.com/questions/9720665/how-to-convert-special-characters-to-normal-characters
		$newStr		= preg_replace($this->_findV1, $this->_replaceV1, $str);
		return $newStr;
	}
}