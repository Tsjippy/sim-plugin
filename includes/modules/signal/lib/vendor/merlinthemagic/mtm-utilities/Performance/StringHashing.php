<?php
//© 2022 Martin Peter Madsen

$mtmBase	= realpath(__DIR__ . "/../../../MTM") ."/";
require_once $mtmBase."mtm-utilities/Enable.php";

$results	= array();
$duration	= array();
$max		= 2147483647;
$max		= 4294967295;

$timeFact	= \MTM\Utilities\Factories::getTime();
$hashFact	= \MTM\Utilities\Factories::getStrings()->getHashing();
foreach (str_split("ABCDEFGHIJKLMNOPQRSTUVWXYZ", 1) as $prefix) {
	
	
	$resArr		= array();
	$sTime		= $timeFact->getMicroEpoch(true);
	for($x=0; $x < 500000; $x++) {
		
		$id	= $prefix.$x;
		
		
		//pick one
		$id		= $hashFact->getAsInteger($id, $max);
// 		$id		= $hashFact->getAsIntegerV2($id, $max);
		
		
		if (array_key_exists($id, $resArr) === false) {
			$resArr[$id]	= $x;
		} else {
			
			$eTime				= $timeFact->getMicroEpoch(true);
			$results[$prefix]	= $x;
			$duration[$prefix]	= ($eTime - $sTime);
			echo $prefix. " -- ".$x." -- ".round($x/($eTime - $sTime), 2)."--\n";
			break;
			
		}
	}
}

echo "\n <code><pre> \nClass:  ".__CLASS__." \nMethod:  ".__FUNCTION__. "  \n";
var_dump((array_sum($results) / count($results)));
echo "\n 2222 \n";
var_dump(array_sum($duration));
echo "\n 3333 \n";
// 			print_r($_POST);
echo "\n ".time()."</pre></code> \n ";
die("end");