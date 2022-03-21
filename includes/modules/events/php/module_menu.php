<?php
namespace SIM\EVENTS;
use SIM;

const ModuleVersion		= '7.0.2';

add_action('sim_submenu_description', function($module_slug, $module_name){
	//module slug should be the same as grandparent folder name
	if($module_slug != basename(dirname(dirname(__FILE__))))	return;

	?>
	<p>
		This module add a custom posttype named event.<br>
		This creates a possibility to add a post with a date, time, location and organizer.<br>
		It also allows the creation of birthdays and anniversaries.<br>
		A calendar displaying all events is accesable at <a href="<?php echo site_url('events');?>"><?php echo site_url('events');?></a>.<br>
		<br>
		It also adds the possibility for schedules: a mealschedule or orientantion schedule or other.<br>
		Add it to any page using the shortcode <code>[schedules]</code>
	</p>
	<?php
},10,2);

add_action('sim_submenu_options', function($module_slug, $module_name, $settings){
	//module slug should be the same as grandparent folder name
	if($module_slug != basename(dirname(dirname(__FILE__))))	return;
	
    ?>
	<label for="freq">How often should we check for expired events?</label>
	<br>
	<select name="freq">
		<?php
		SIM\ADMIN\recurrenceSelector($settings['freq']);
		?>
	</select>
	<br>
	<br>
	<label>Minimum age of events before they get removed:<br></label>
	<select name="max_age">
		<option value=''>---</option>
		<option value='1 day' <?php if($settings["max_age"] == '1 day') echo 'selected';?>>1 day</option>
		<option value='1 week' <?php if($settings["max_age"] == '1 week') echo 'selected';?>>1 week</option>
		<option value='1 month' <?php if($settings["max_age"] == '1 month') echo 'selected';?>>1 month</option>
		<option value='3 months' <?php if($settings["max_age"] == '3 months') echo 'selected';?>>3 months</option>
		<option value='1 year' <?php if($settings["max_age"] == '1 year') echo 'selected';?>>1 year</option>
	</select>	
	<?php
}, 10, 3);

add_action('sim_module_updated', function($module_slug, $options){
	//module slug should be the same as grandparent folder name
	if($module_slug != basename(dirname(dirname(__FILE__))))	return;

	schedule_tasks();
}, 10, 2);