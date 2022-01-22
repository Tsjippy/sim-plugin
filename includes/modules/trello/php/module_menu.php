<?php
namespace SIM;

add_action('sim_submenu_options', function($module_slug, $module_name, $settings){
	//module slug should be the same as grandparent folder name
	if($module_slug != basename(dirname(dirname(__FILE__))))	return;
	
	global $Trello;

	//Check if we need to update the trello webhook
	if(!empty($_POST['list']) and $_POST['list'] != $settings['list']){
		$Trello->change_webhook_id($Trello->get_webhooks()[0]->id, $_POST['list']);
	}

	//Trello token has changed
	if($_POST['token'] != $settings['token']){
		//remove all webhooks from the old token
		$Trello->delete_all_webhooks();
		
		//remove trello info belonging to the old token
		unset($_POST['trello_list']);
		unset($_POST['trello_board']);
		unset($_POST['trello_destination_list']);

		//Now that the new trello token is set, lets create new webhooks
		$new_trello = new trello();
		
		//remove all webhooks from the new token
		$new_trello->delete_all_webhooks();
		
		//Get the userid belonging to the new token
		$trello_user_id = $new_trello->get_token_info()->id;
		
		//Create a webhook listening to the userid	
		$new_trello->create_webhook('https://simnigeria.org/trello_webhook', $trello_user_id, "Listens to all actions related to the user with id $trello_user_id");
	}

	//Trelle webhook page
	?>
	<label>
		Trello Webhook page
	</label>
	<?php echo page_select("webhook_page", $settings["webhook_page"]);?>
	
	<br>
	<label>
		Trello API key
		<input type="text" name="key" value="<?php echo $settings["key"]; ?>" style="width:100%;">
	</label>
	<br>
	<label>
		Trello API token
		<input type="text" name="token" value="<?php echo $settings["token"]; ?>" style="width:100%;">
	</label>
	<br>
	<?php
	if(isset($settings["key"]) and isset($settings["token"]) and strpos(get_site_url(), 'localhost') === false){
		?>
		<label>
			Trello board you want listen to
		</label>
		<select name='board'>
			<option value="">---</option>
			<?php
			$Trello->get_boards();
			foreach ($Trello->get_boards() as $name=>$id){
				if($settings["board"] == $id){
					$selected = 'selected="selected"';
				}else{
					$selected = '';
				}
				echo "<option value='$id' $selected>$name</option>";
			}
			?>
		</select>
		<?php
	}
}, 10, 3);

