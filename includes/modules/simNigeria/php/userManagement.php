<?php
namespace SIM\SIMNIGERIA;
use SIM;

add_filter('sim_submenu_options', function($optionsHtml, $moduleSlug, $settings){
	//module slug should be the same as grandparent folder name
	if($moduleSlug != 'usermanagement'){
		return $optionsHtml;
	}

	ob_start();
	?>
	<label>How long in advance (in months) should we warn people about vaccinations who are about to expire?</label>
	<select name="vaccination_warning_time">
		<?php
		for ($x = 0; $x <= 12; $x++) {
			if($settings['vaccination_warning_time'] === strval($x)){
				$selected = 'selected="selected"';
			}else{
				$selected = '';
			}
			echo "<option value='$x' $selected>$x</option>";
		}
		?>
	</select>
	<br>
	<label for="greencard_reminder_freq">How often should people be reminded of their greencard expiry?</label>
	<br>
	<select name="greencard_reminder_freq">
		<?php
		SIM\ADMIN\recurrenceSelector($settings['greencard_reminder_freq']);
		?>
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

	return $optionsHtml.ob_get_clean();
}, 10, 3);