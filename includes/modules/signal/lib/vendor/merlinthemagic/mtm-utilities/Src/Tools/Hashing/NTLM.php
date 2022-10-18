<?php
//© 2021 Martin Peter Madsen
namespace MTM\Utilities\Tools\Hashing;

class NTLM
{
	public function get($str)
	{
		if (is_string($str) === true) {
			//src: https://www.php.net/manual/en/ref.hash.php
			$rData		= iconv("UTF-8", "UTF-16LE", $str);
			$rData		= bin2hex(mhash(MHASH_MD4, $rData));
			return strtoupper($rData);
		} else {
			throw new \Exception("Invalid input");
		}
	}
}