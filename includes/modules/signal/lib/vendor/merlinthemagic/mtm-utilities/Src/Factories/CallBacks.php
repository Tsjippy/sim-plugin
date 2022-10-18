<?php
//© 2019 Martin Peter Madsen
namespace MTM\Utilities\Factories;

class CallBacks
{	
	//USE: $utilObj		= \MTM\Utilities\Factories::getCallBacks()->__METHOD__();
	
	protected $_s=array();

	public function getProcess()
	{
		//old alias
		return \MTM\Utilities\Factories::getProcesses()->getCallBacks();
	}
}