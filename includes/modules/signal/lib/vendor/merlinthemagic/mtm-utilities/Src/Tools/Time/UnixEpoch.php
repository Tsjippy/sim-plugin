<?php
//© 2019 Martin Peter Madsen
namespace MTM\Utilities\Tools\Time;

class UnixEpoch extends \MTM\Utilities\Tools\Base
{
	public function getFromUtcByFormat($input, $format)
	{
		//assumes the date was generated on a system observing UTC
		//this correct for system timezone and return epoch 
		return $this->getByFormatTimezone($input, $format, "UTC");
	}
	public function getByFormatTimezone($input, $format, $tzStr)
	{
		//Examples:
		//$input: jun/08/2021-13:47:07, format: "M/d/Y-H:i:s", TZ: CET
		$date = \DateTime::createFromFormat($format, $input, new \DateTimeZone($tzStr));
		return intval($date->format("U"));
	}
}