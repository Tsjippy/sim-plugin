<?php
namespace SIM\MAILCHIMP;
use SIM;

const ModuleVersion		= '7.0.0';

add_action('sim_submenu_description', function($module_slug, $module_name){
	//module slug should be the same as grandparent folder name
	if($module_slug != basename(dirname(dirname(__FILE__))))	return;

	?>
	<p>
		This module adds the possibility to send e-mails via Mailchimp. <br>
		Post or page contents can be send as e-mail upon publishing or updating.<br>
		Create your api key for Mailchimp <a href='https://mailchimp.com/developer/marketing/guides/quick-start/'>here</a>.<br>
	</p>
	<?php

},10,2);

add_action('sim_submenu_options', function($module_slug, $module_name, $settings){
	//module slug should be the same as grandparent folder name
	if($module_slug != basename(dirname(dirname(__FILE__))))	return;
	
	if(!class_exists(__NAMESPACE__.'\Mailchimp')){
		require(__DIR__.'/mailchimp.php');
	}

	?>
	<label>
		Mailchimp API key
		<input type="text" name="apikey" id="apikey" value="<?php echo $settings["apikey"]; ?>" style="width:100%;">
	</label>
	<br>
	<label>
		Mailchimp audience(s) you want new users added to:<br>
		<?php
		$Mailchimp = new Mailchimp();
		$lists = (array)$Mailchimp->get_lists();
		foreach ($lists as $key=>$list){
			if($settings["audienceids"][$key]==$list->id){
				$checked = 'checked="checked"';
			}else{
				$checked = '';
			}
			echo '<label>';
				echo "<input type='checkbox' name='audienceids[$key]' value='$list->id' $checked>";
				echo $list->name;
			echo '</label><br>';
		}				
		?>
	</label>
	<br>
	<label>
		Mailchimp TAGs you want to add to new users<br>
		<input type="text" name="user_tags" value="<?php echo $settings["user_tags"]; ?>">
	</label>
	<br>
	<br>
	<label>
		Mailchimp TAGs you want to add to missionaries<br>
		<input type="text" name="missionary_tags" value="<?php echo $settings["missionary_tags"]; ?>">
	</label>
	<br>
	<br>
	<label>
		Mailchimp TAGs you want to add to office staff<br>
		<input type="text" name="office_staff_tags" value="<?php echo $settings["office_staff_tags"]; ?>">
	</label>
	<br>
	<br>
	<label>Mailchimp template to be used for e-mails</label>
	<?php
	$templates	= $Mailchimp->get_templates();
	?>
	<select name="templateid">
		<?php
		foreach($templates as $template){
			if($template->id == $settings["templateid"]){
				$selected	= 'selected';
			}else{
				$selected	= '';
			}
			echo "<option value='$template->id' $selected>$template->name</option>";
		}
		?>
	</select>
	<?php
}, 10, 3);
