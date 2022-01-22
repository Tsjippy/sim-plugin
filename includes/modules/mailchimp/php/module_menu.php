<?php
namespace SIM;

add_action('sim_submenu_options', function($module_slug, $module_name, $settings){
	//module slug should be the same as grandparent folder name
	if($module_slug != basename(dirname(dirname(__FILE__))))	return;
	
	if(!class_exists('Mailchimp')){
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
		Mailchimp TAGs you want to add to new users
		<input type="text" name="user_tags" value="<?php echo $settings["user_tags"]; ?>">
	</label>
	<br>
	<label>
		Mailchimp TAGs you want to add to missionaries
		<input type="text" name="missionary_tags" value="<?php echo $settings["missionary_tags"]; ?>">
	</label>
	<br>
	<label>
		Mailchimp TAGs you want to add to office staff
		<input type="text" name="office_staff_tags" value="<?php echo $settings["office_staff_tags"]; ?>">
	</label>
	<br>
	<label>
		Mailchimp template id
		<input type="text" name="templateid" value="<?php echo $settings["templateid"]; ?>" style="width:100%;">
	</label>
	<?php
}, 10, 3);
