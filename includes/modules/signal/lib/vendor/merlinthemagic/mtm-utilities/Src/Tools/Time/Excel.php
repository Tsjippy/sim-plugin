<?php
//© 2019 Martin Peter Madsen
namespace MTM\Utilities\Tools\Time;

class Excel extends \MTM\Utilities\Tools\Base
{
	public function getEpoch($excelDate)
	{
		return (intval($excelDate) - 25569) * 86400;
	}
}