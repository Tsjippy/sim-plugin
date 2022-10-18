<?php
//© 2021 Martin Peter Madsen
namespace MTM\Utilities\Tools\Hashing;

class SHA
{
	public function isValid256($str)
	{
		if (is_string($str) === true && preg_match("/^([0-9a-fA-F]{64})$/", $str) === 1) {
			return true;
		} else {
			return false;
		}
	}
	public function isValid512($str)
	{
		if (is_string($str) === true && preg_match("/^([0-9a-fA-F]{128})$/", $str) === 1) {
			return true;
		} else {
			return false;
		}
	}
}