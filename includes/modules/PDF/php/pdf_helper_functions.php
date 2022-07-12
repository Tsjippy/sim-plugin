<?php
namespace SIM\PDF;
use SIM;

/**
 * Modified from http://www.fpdf.org/en/script/script42.php
 */

/**
 * returns an associative array (keys: R,G,B) from
 * a hex html code (e.g. #3FE5AA)
 * 
 * @param	string	$color	The Color to process
 * 
 * @return	array			The RGB array
 */
function hex2dec($color = "#000000"){
    $R 				= substr($color, 1, 2);
    $red 			= hexdec($R);
    $V 				= substr($color, 3, 2);
    $green 			= hexdec($V);
    $B 				= substr($color, 5, 2);
    $blue 			= hexdec($B);
    $tblColor 		= array();
    $tblColor['R']	= $red;
    $tblColor['G']	= $green;
    $tblColor['B']	= $blue;
    return $tblColor;
}

/**
 * conversion pixel -> millimeter at 72 dpi
 * 
 * @param	int		$px		The pixel amount
 * 
 * @return	float			The mm
 */
function px2mm($px){
    return $px*25.4/72;
}

/**
 * Get text from html
 * 
 * @param	string	$html	The html to parse
 * 
 * @return	string			the text
 */
function txtentities($html){
    $trans = get_html_translation_table(HTML_ENTITIES);
    $trans = array_flip($trans);
    return strtr($html, $trans);
}