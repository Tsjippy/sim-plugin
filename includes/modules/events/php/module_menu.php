<?php
namespace SIM\EVENTS;
use SIM;

const MODULE_VERSION		= '7.0.7';
//module slug is the same as grandparent folder name
DEFINE(__NAMESPACE__.'\MODULE_SLUG', basename(dirname(dirname(__FILE__))));

add_action('sim_submenu_description', function($moduleSlug){
	//module slug should be the same as grandparent folder name
	if($moduleSlug != MODULE_SLUG)	{return;}

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

	<p>
		<strong>Auto created page:</strong><br>
		<a href='<?php echo home_url('/events');?>'>Calendar</a><br>
	</p>
	<?php
});

add_action('sim_submenu_options', function($moduleSlug, $settings){
	//module slug should be the same as grandparent folder name
	if($moduleSlug != MODULE_SLUG)	{return;}
	
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
		<?php
			$strings	= [
				'1 day',
				'1 week',
				'1 month',
				'3 months',
				'1 year'
			];

			foreach($strings as $string){
				$selected	= '';
				if($settings["max_age"] == $string){
					$selected	= 'selected';
				}

				echo "<option value='$string' $selected>$string</option>";
			}
		?>
	</select>	

	<br>
	<br>
	<h4>Default picture for birthdays</h4>
	<?php
	SIM\pictureSelector('birthday_image', 'Birthday', $settings);
	?>
	<br>
	<br>
	<h4>Default picture for anniversaries</h4>
	<?php
	SIM\pictureSelector('anniversary_image', 'Anniversary', $settings);
}, 10, 3);

add_filter('sim_module_updated', function($options, $moduleSlug){
	//module slug should be the same as grandparent folder name
	if($moduleSlug != MODULE_SLUG){
		return $options;
	}

	$events	= new Events();
	$events->createEventsTable();

	$schedules	= new Schedules();
	$schedules->createDbTable();

	scheduleTasks();

	return $options;
}, 10, 2);