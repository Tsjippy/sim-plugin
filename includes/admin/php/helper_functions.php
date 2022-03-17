<?php
namespace SIM\ADMIN;
use SIM;

function recurrenceSelector($curFreq){
	?>
	<option value=''>---</option>
	<option value='daily' <?php if($curFreq == 'daily') echo 'selected';?>>Daily</option>
	<option value='weekly' <?php if($curFreq == 'weekly') echo 'selected';?>>Weekly</option>
	<option value='monthly' <?php if($curFreq == 'monthly') echo 'selected';?>>Monthly</option>
	<option value='threemonthly' <?php if($curFreq == 'threemonthly') echo 'selected';?>>Every quarter</option>
	<option value='sixmonthly' <?php if($curFreq == 'sixmonthly') echo 'selected';?>>Every half a year</option>
	<option value='yearly' <?php if($curFreq == 'yearly') echo 'selected';?>>Yearly</option>
	<?php
}