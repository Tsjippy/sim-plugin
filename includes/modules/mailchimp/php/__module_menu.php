<?php
namespace SIM\MAILCHIMP;
use SIM;

const MODULE_VERSION		= '7.0.0';
//module slug is the same as grandparent folder name
DEFINE(__NAMESPACE__.'\MODULE_SLUG', strtolower(basename(dirname(__DIR__))));

DEFINE(__NAMESPACE__.'\MODULE_PATH', plugin_dir_path(__DIR__));

add_filter('sim_submenu_description', function($description, $moduleSlug){
	//module slug should be the same as the constant
	if($moduleSlug != MODULE_SLUG)	{
		return $description;
	}

	ob_start();
	?>
	<p>
		This module adds the possibility to send e-mails via Mailchimp. <br>
		Post or page contents can be send as e-mail upon publishing or updating.<br>
		Create your api key for Mailchimp <a href='https://mailchimp.com/developer/marketing/guides/quick-start/'>here</a>.<br>
	</p>
	<?php

	return ob_get_clean();
}, 10, 2);

add_filter('sim_submenu_options', function($optionsHtml, $moduleSlug, $settings){
	//module slug should be the same as grandparent folder name
	if($moduleSlug != MODULE_SLUG){
		return $optionsHtml;
	}

	ob_start();
	
	?>
	Default picture for imported Mailchimp campaigns
	<?php
	SIM\pictureSelector('imageId', 'Default Picture', $settings);
	?>
	<label>
		Mailchimp API key
		<input type="text" name="apikey" id="apikey" value="<?php echo $settings["apikey"]; ?>" style="width:100%;">
	</label>

	<?php
	if(!empty($settings["apikey"])){
		$mailchimp = new Mailchimp();
		?>
		<br>
		<label>
			Mailchimp audience(s) you want new users added to:<br>
			<?php
			
			$lists = (array)$mailchimp->getLists();
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
		<label>
			Mailchimp template to be used for e-mails<br>
			Make sure it contains the text '//*THIS WILL BE REPLACED BY THE WEBSITE *//'
		</label>
		<?php
		$templates	= $mailchimp->getTemplates();
		?>
		<select name="templateid">
			<?php
			foreach($templates as $template){
				if($template->id == $settings["templateid"]){
					$selected	= 'selected="selected"';
				}else{
					$selected	= '';
				}
				echo "<option value='$template->id' $selected>$template->name</option>";
			}
			?>
		</select>
		<?php
	}

	return ob_get_clean();
}, 10, 3);

add_filter('sim_module_updated', function($newOptions, $moduleSlug, $oldOptions){
	//module slug should be the same as grandparent folder name
	if($moduleSlug != MODULE_SLUG){
		return $newOptions;
	}

	scheduleTasks();

	return $newOptions;
}, 10, 3);