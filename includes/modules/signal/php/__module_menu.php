<?php
namespace SIM\SIGNAL;
use SIM;

const MODULE_VERSION		= '7.0.0';
//module slug is the same as grandparent folder name
DEFINE(__NAMESPACE__.'\MODULE_SLUG', strtolower(basename(dirname(__DIR__))));

add_filter('sim_submenu_description', function($description, $moduleSlug){
	//module slug should be the same as the constant
	if($moduleSlug != MODULE_SLUG)	{
		return $description;
	}

	ob_start();
	?>
	<p>
		This module adds the possibility to send Signal messages to users or groups.<br>
		An external Signal Bot can read and delete these messages and send them on Signal.<br>
		<br>
		Add one shortcode: 'signal_messages'.<br>
		Use this shortcode to display all pending Signal messages and delete them if needed.<br>
		<br>
		Adds 2 urls to the rest api:<br>
		sim/v2/notifications to get any other messages<br>
		sim/v2/firstname to find a users name by phonenumber<br>
	</p>
	<?php
	return ob_get_clean();
}, 10, 2);

add_filter('sim_submenu_options', function($optionsHtml, $moduleSlug, $settings){
	//module slug should be the same as grandparent folder name
	if($moduleSlug != MODULE_SLUG){
		return $optionsHtml;
	}

	if(empty($settings['groups'])){
		$groups	= [''];
	}else{
		$groups	= $settings['groups'];
	}

	ob_start();
	
	?>
	<label>
		Link to join the Signal group
		<input type='url' name='group_link' value='<?php echo $settings["group_link"]; ?>' style='width:100%'>
	</label>

	<div class="">
		<h4>Give optional Signal group name(s) to send new content messages to:</h4>
		<div class="clone_divs_wrapper">
			<?php
			foreach($groups as $index=>$group){
				?>
				<div class="clone_div" data-divid="<?php echo $index;?>">
					<label>
						<h4 style='margin: 0px;'>Signal groupname <?php echo $index+1;?></h4>
						<input type='text' name="groups[<?php echo $index;?>]" value='<?php echo $group;?>'>
					</label>
					<span class='buttonwrapper' style='margin:auto;'>
						<button type="button" class="add button" style="flex: 1;">+</button>
						<?php
						if(count($groups)> 1){
							?>
							<button type="button" class="remove button" style="flex: 1;">-</button>
							<?php
						}
						?>
					</span>
				</div>
				<?php
			}
			?>
		</div>
	</div>
	<?php

	return ob_get_clean();
}, 10, 3);

add_filter('sim_module_data', function($dataHtml, $moduleSlug, $settings){
	//module slug should be the same as grandparent folder name
	if($moduleSlug != MODULE_SLUG){
		return $dataHtml;
	}

	if(empty($settings['groups'])){
		$groups	= [''];
	}else{
		$groups	= $settings['groups'];
	}

	// check if we need to send a message
	if(!empty($_POST['message']) && !empty($_POST['recipient'])){
        sendSignalMessage($_POST['message'], $_POST['recipient']);
        ?>
		<div class='success'>
			Message send succesfully
		</div>
		<?php
    }

	ob_start();

	?>
	<form method='post'>
		<label>
			<h4>Message to be send</h4>
			<input type='text' name='message' style='width:100%;' required>
		</label>
		<label>
			<h4>Recipient</h4>
			<input type='text' name='recipient' list='groups' required>

			<datalist id='groups'>
				<?php
				foreach($groups as $group){
					echo "<option>$group</option>";
				}
				?>
			</datalist>
		</label>
		<br>
		<br>
		<button>Send message</button>
	</form>
	<?php

	return ob_get_clean();
}, 10, 3);