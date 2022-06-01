<?php
namespace SIM\TRELLO;
use SIM;

const ModuleVersion		= '7.0.0';

add_action('sim_submenu_description', function($moduleSlug, $moduleName){
	//module slug should be the same as grandparent folder name
	if($moduleSlug != basename(dirname(dirname(__FILE__))))	return;

	?>
	<p>
		This module creates an integration with trello.<br>
		It is able to import new users and update existing users based on trello card contents.<br>
	</p>
	<?php

},10,2);

add_action('sim_submenu_options', function($moduleSlug, $moduleName, $settings){
	//module slug should be the same as grandparent folder name
	if($moduleSlug != basename(dirname(dirname(__FILE__))))	return;

	$trello	= new Trello();
	
	//Trelle webhook page
	?>	
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
	if(isset($settings["key"]) and isset($settings["token"]) and strpos(SITEURL, 'localhost') === false){
		?>
		<label>
			Trello board you want listen to
		</label>
		<select name='board'>
			<option value="">---</option>
			<?php
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

add_filter('sim_module_updated', function($newOptions, $moduleSlug, $oldOptions){
	//module slug should be the same as grandparent folder name
	if($moduleSlug != basename(dirname(dirname(__FILE__))))	return $newOptions;

	//Trello token has changed
	if($oldOptions['token'] != $newOptions['token']){
		$trello	= new Trello();
		//remove all webhooks from the old token
		$trello->deleteAllWebhooks();

		//Now that the new trello token is set, lets create new webhooks
		$Modules[$moduleSlug]	= $newOptions;

		// Inititate a new trello object with the new token
		$trello = new trello();
		
		//remove all webhooks from the new token
		$trello->deleteAllWebhooks();
		
		//Get the userid belonging to the new token
		$trelloUserId = $trello->getTokenInfo()->id;
		
		//Create a webhook listening to the userid	
		$trello->createWebhook(SITEURL.'/wp-json/sim/v1/trello', $trelloUserId, "Listens to all actions related to the user with id $trelloUserId");
	}

	return $newOptions;
	
}, 10, 3);