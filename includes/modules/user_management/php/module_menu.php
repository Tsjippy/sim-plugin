<?php
namespace SIM\USERMANAGEMENT;
use SIM;

const ModuleVersion		= '7.0.0';

add_action('sim_submenu_description', function($module_slug, $module_name){
	//module slug should be the same as grandparent folder name
	if($module_slug != basename(dirname(dirname(__FILE__))))	return;

	?>
	<p>
		This module depends on the forms module. Only activate this module when the forms module is active already.<br>
		<br>
		This module adds 5 shortcodes:
		<h4>user-info</h4>
		This shortcode displays all forms to set and update userdata.<br>
		You can also change userdata for other users if you have the 'usermanagement' role.<br>
		Use like this: <code>[user-info currentuser='true']</code>
		<br>
		<h4>create_temp_user</h4>
		This shortcode displays a from to create new user accounts.<br>
		Use like this: <code>[create_temp_user]</code>
		<br>
		<h4>pending_user</h4>
		This shortcode displays all user account who are pending approval.<br>
		Use like this: <code>[pending_user]</code>
		<br>
		<h4>change_password</h4>
		This shortcode displays a form for users to reset their password.<br>
		Use like this: <code>[change_password]</code>
	</p>
	<?php
},10,2);

add_action('sim_submenu_options', function($module_slug, $module_name, $settings){
	//module slug should be the same as grandparent folder name
	if($module_slug != basename(dirname(dirname(__FILE__))))	return;
	
	?>
	<label>
		<input type='checkbox' name='tempuser' value='tempuser' <?php if(isset($settings['tempuser'])) echo 'checked';?>>
		Enable temporary user accounts
	</label>
	<br>
	<label>How long in advance (in months) should we warn people about vaccinations who are about to expire?</label>
	<select name="vaccination_warning_time">
		<option value="1">1</option>
		<option value="2">2</option>
		<option value="3">3</option>
		<option value="4">4</option>
		<option value="5">5</option>
		<option value="6">6</option>
		<option value="7">7</option>
		<option value="8">8</option>
		<option value="9">9</option>
		<option value="10">10</option>
		<option value="11">11</option>
		<option value="12">12</option>
	</select>
	<br>
	<label>
		E-mail address of the healthcare coordinator<br>
		<input type="email" name="health_email" value="<?php echo $settings['health_email'];?>">
	</label>
	<br>
	<label>
		E-mail address of the personnel coordinator<br>
		<input type="email" name="personnel_email" value="<?php echo $settings['personnel_email'];?>">
	</label>
	<br>
	<label>
		E-mail address of the short term coordinator<br>
		<input type="email" name="sta_email" value="<?php echo $settings['sta_email'];?>">
	</label>
	<br>
	<?php
    
}, 10, 3);

add_action('sim_module_updated', function($module_slug, $options){
	//module slug should be the same as grandparent folder name
	if($module_slug != basename(dirname(dirname(__FILE__))))	return;

	schedule_tasks();
}, 10, 2);