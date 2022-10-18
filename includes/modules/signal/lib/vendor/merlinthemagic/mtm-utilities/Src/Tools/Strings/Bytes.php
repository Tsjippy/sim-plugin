<?php
//© 2020 Martin Peter Madsen
namespace MTM\Utilities\Tools\Strings;

class Bytes
{
	public function hexToByteString($str)
	{
		$rData	= "";
		foreach (str_split($str, 2) as $val) {
			$rData	.= chr(hexdec($val));
		}
		return $rData;
	}
}