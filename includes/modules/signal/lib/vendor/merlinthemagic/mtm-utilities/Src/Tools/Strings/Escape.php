<?php
//© 2020 Martin Peter Madsen
namespace MTM\Utilities\Tools\Strings;

class Escape
{
	public function getSingleQuotedShellCmd($str)
	{
		//escapeshellarg and escapeshellcmd will escape lots of chars that
		//are already made safe by the shell treating char literally when enclosed in single quotes
		if (is_string($str) === false) {
			throw new \Exception("Invalid input");
		}
		//src: https://stackoverflow.com/questions/15783701/which-characters-need-to-be-escaped-when-using-bash
		//ending \ are also treated literally
		$strCmd	= str_replace("'", "'\''", $str);
		return "'".$strCmd."'";
	}
}