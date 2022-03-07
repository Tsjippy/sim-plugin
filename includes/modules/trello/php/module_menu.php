<?php
namespace SIM\TRELLO;
use SIM;

const ModuleVersion		= '7.0.0';

add_action('sim_submenu_description', function($module_slug, $module_name){
	//module slug should be the same as grandparent folder name
	if($module_slug != basename(dirname(dirname(__FILE__))))	return;

	?>
	<p>
		This module creates an integration with trello.<br>
		It is able to import new users and update existing users based on trello card contents.<br>
	</p>
	<?php

},10,2);

add_action('sim_submenu_options', function($module_slug, $module_name, $settings){
	//module slug should be the same as grandparent folder name
	if($module_slug != basename(dirname(dirname(__FILE__))))	return;

	$trello	= new Trello();
	
	//Check if we need to update the trello webhook
	if(!empty($_POST['webhook_page']) and $_POST['webhook_page'] != $settings['webhook_page']){
		$trello->changeWebhookId($trello->getWebhooks()[0]->id, $_POST['webhook_page']);
	}

	//Trello token has changed
	if($_POST['token'] != $settings['token']){
		//remove all webhooks from the old token
		$trello->deleteAllWebhooks();
		
		//remove trello info belonging to the old token
		unset($_POST['trello_list']);
		unset($_POST['trello_board']);
		unset($_POST['trello_destination_list']);

		//Now that the new trello token is set, lets create new webhooks
		$new_trello = new trello();
		
		//remove all webhooks from the new token
		$new_trello->deleteAllWebhooks();
		
		//Get the userid belonging to the new token
		$trello_user_id = $new_trello->getTokenInfo()->id;
		
		//Create a webhook listening to the userid	
		$new_trello->createWebhook('https://simnigeria.org/trello_webhook', $trello_user_id, "Listens to all actions related to the user with id $trello_user_id");
	}

	//Trelle webhook page
	?>
	<label>
		Trello Webhook page
	</label>
	<?php echo SIM\page_select("webhook_page", $settings["webhook_page"]);?>
	
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
			$trello->getBoards();
			foreach ($trello->getBoards() as $name=>$id){
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

