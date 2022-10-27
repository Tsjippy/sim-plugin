<?php
namespace SIM\SIGNAL;
use SIM;

const MODULE_VERSION		= '7.0.0';
//module slug is the same as grandparent folder name
DEFINE(__NAMESPACE__.'\MODULE_SLUG', strtolower(basename(dirname(__DIR__))));

DEFINE(__NAMESPACE__.'\MODULE_PATH', str_replace('\\', '/', plugin_dir_path(__DIR__)));

add_filter('sim_submenu_description', function($description, $moduleSlug){
	//module slug should be the same as the constant
	if($moduleSlug != MODULE_SLUG)	{
		return $description;
	}
	
	ob_start();
	?>
	<p>
		This module adds the possibility to send Signal messages to users or groups.<br>
		<?php
		if(empty(shell_exec('javac -version'))){
			?>
			An external Signal Bot can read and delete these messages and send them on Signal.<br>
			Get an example from <a href="<?php echo SIM\pathToUrl(PLUGINPATH.'/other/SignalBot.py');?>">here</a><br>
			<br>
			Adds one shortcode: 'signal_messages'.<br>
			Use this shortcode to display all pending Signal messages and delete them if needed.<br>
			<?php
		}else{
			?>
			Java JDK is installed, you are ready to go
			<?php
		}
		?>
	</p>
	<?php
	return ob_get_clean();
}, 10, 2);

function registerForm(){
	?>
	<form method='post' action='<?php echo admin_url( "admin.php?page={$_GET['page']}&tab={$_GET['tab']}" );?>'>
		<h4>Register with Signal</h4>
		First get a captcha, see <a href='https://github.com/AsamK/signal-cli/wiki/Registration-with-captcha' target=_blank>here</a>
		<br>
		<label>
			Captcha:
			<br>
			<input type='text' name='captcha' style='width:100%' required>
		</label>
		<br>
		<label>
			<input type='checkbox' name='voice' value='true'>
			Register with a voice call in stead of sms
		</label>
		<br>
		<br>
		<button>Register</button>
	</form>
	<?php
}

add_action('sim-admin-settings-post', function(){
	global $Modules;

	$signal = new Signal();

	if(isset($_GET['unregister'])){
		$signal->unregister();

		// remove the folder
		$path   = str_replace('/', '\\', $signal->profilePath);
		exec("rmdir $path /s /q");

		// remove the phone number
		unset($Modules[MODULE_SLUG]['phone']);
		update_option('sim_modules', $Modules);
	}elseif(isset($_GET['register'])){
		registerForm();
	}elseif(!empty($_POST['captcha'])){
		echo $signal->register($_POST['captcha'], isset($_POST['voice']));

		if(!empty($signal->error)){
			registerForm();
		}else{
			?>
			<form method='post'>
				<label>
					Verification code
					<input type='number' name='verification-code' required>
				</label>

				<br>
				<br>
				<button>Verify</button>
			</form>
			<?php
		}
	}elseif(!empty($_POST['verification-code'])){
		echo $signal->verify(isset($_POST['verification-code']));
		if(empty($signal->error)){
			unset($_POST['verification-code']);
		}else{
			registerForm();
		}
	}elseif(isset($_GET['link'])){
		echo $signal->link();
	}
});

 /**
 * Checks if the current phone number is connected with Signal
 *
 * @param	object	$signal		The signal object
 *
 * @return 	bool					true when connected
 */
function checkIfConnected($signal){
	if(!file_exists($signal->profilePath.'/data/accounts.json') || empty($signal->username)){
		return false;
	}

	$config	= json_decode(file_get_contents($signal->profilePath.'/data/accounts.json'));

	if(empty($config) || empty($config->accounts)){
		return false;
	}

	foreach($config->accounts as $account){
		if($account->number == $signal->username){
			return true;
		}
	}

	return false;
}

 /**
 * Shows the options when connected to Signal
 *
 * @param	object	$signal		The signal object
 * @param	array	$settings	The module settings
 */
function connectedOptions($signal, $settings){
	$url		= admin_url( "admin.php?page={$_GET['page']}&tab={$_GET['tab']}" );

	$signalGroups	= $signal->listGroups();
	if(!empty($signal->error)){
		echo $signal->error;
	}

	?>
	<input type='hidden' name="phone" value='<?php echo $settings["phone"]; ?>'>
	<h4>Connection details</h4>
	<p>
		Currently connected to <?php echo $settings["phone"]; ?>
		<a href='<?php echo $url;?>&unregister=<?php echo $settings["phone"]; ?>' class='button'>Unregister</a>
	</p>
	<?php
	if(!empty($signalGroups)){
		?>
		<div class="">
			<h4>Select optional Signal group(s) to send new content messages to:</h4>
			<?php

			if(empty($settings['groups'])){
				$settings['groups']	= [];
			}

			foreach($signalGroups as $group){
				if(empty($group->name)){
					continue;
				}
				?>
				<label>
					<input type='checkbox' name='groups[]' value='<?php echo $group->id;?>' <?php if(in_array($group->id, $settings['groups'])){echo 'checked';}?>>
					<?php echo $group->name;?>
				</label>
				<br>
				<?php
			}
			?>
		</div>
		<?php
	}
}

 /**
 * Shows the options when not connected to Signal
 *
 * @param	array	$settings		The module settings
 */
function notConnectedOptions($settings){
	$url		= admin_url( "admin.php?page={$_GET['page']}&tab={$_GET['tab']}" );

	?>
	<label>
		Phone number to be used for sending messages
		<input type="tel" name="phone" pattern="\+[0-9]{9,}" title="Phonenumber starting with a +. Only numbers. Example: +2349041234567" value='<?php echo $settings["phone"]; ?>' style='width:100%'>
	</label>
	<?php
	if(!empty($settings["phone"])){
		?>
		<h4>Connection details</h4>
		<p>
			Currently not connected to <?php echo $settings["phone"]; ?>
			<br>
			<a href='<?php echo $url;?>&register=<?php echo $settings["phone"]; ?>' class='button'>Register this number with Signal</a>
			<br>
			<a href='<?php echo $url;?>&link=<?php echo $settings["phone"]; ?>' class='button'>Link to an existing Signal number</a>
		</p>
		<?php
	}
}

 /**
 * Shows the options when Java JDK is not installed
 *
 * @param	array	$settings		The module settings
 */
function notLocalOptions($settings){
	if(empty($settings['groups'])){
		$groups	= [''];
	}else{
		$groups	= $settings['groups'];
	}

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
}

add_filter('sim_submenu_options', function($optionsHtml, $moduleSlug, $settings){
	global $Modules;

	//module slug should be the same as grandparent folder name
	if($moduleSlug != MODULE_SLUG || isset($_GET['register']) || $_POST['captcha'] || $_POST['verification-code']){
		return $optionsHtml;
	}

	ob_start();

	$local	= true;
	if(empty(shell_exec('javac -version'))){
		$local = false;
        echo "<div class='warning'>";
			echo "Java JDK is not installed.<br>You need to install Java JDK.<br>";
			if(strpos(php_uname(), 'Linux') !== false){
				echo "Try <code>sudo apt install openjdk-17-jdk -y</code>";
			}
			echo "<br><br>We assume you are on a shared host for now, which means you have to run an external script to send signal messages<br>";
			
		echo "</div>";
    }

	
	// store in settings
	echo "<input type='hidden' name='local' value='$local'>";
	
	if($local){
		// store in settings
		if(!isset($settings['local'])){
			$Modules[MODULE_SLUG]['local']	= true;
			update_option('sim_modules', $Modules);
		}

		$signal 	= new Signal();

		if(checkIfConnected($signal)){
			connectedOptions($signal, $settings);
		}else{
			notConnectedOptions($settings);
		}
	}else{
		notLocalOptions($settings);
	}

	return ob_get_clean();
}, 10, 3);

add_filter('sim_module_functions', function($dataHtml, $moduleSlug, $settings){
	//module slug should be the same as grandparent folder name
	if($moduleSlug != MODULE_SLUG){
		return $dataHtml;
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

	$phonenumbers	= '';
	foreach (get_users() as $user) {
		$phones	= (array)get_user_meta($user->ID, 'phonenumbers', true);
		foreach($phones as $phone){
			$phonenumbers	.= "<option value='$phone'>$user->display_name ($phone)</option>";
		}
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
			<input type='text' name='recipient' list='groups' style='width:100%' required>

			<datalist id='groups'>
				<?php
				echo $phonenumbers;
				if(isset($settings['local']) && $settings['local']){
					$signal = new Signal();

					if(file_exists($signal->profilePath)){
						$groups	= $signal->listGroups();

						foreach($groups as $group){
							if(empty($group->name)){
								continue;
							}
							echo "<option value='$group->id'>$group->name</option>";
						}
					}
				}else{
					if(empty($settings['groups'])){
						$groups	= [''];
					}else{
						$groups	= $settings['groups'];
					}
					foreach($groups as $group){
						echo "<option value='$group'>$group</option>";
					}
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

add_filter('sim_module_updated', function($options, $moduleSlug, $oldSettings){
	//module slug should be the same as grandparent folder name
	if($moduleSlug != MODULE_SLUG){
		return $options;
	}

	$signal = new Signal();
	$signal->receive();

	if(isset($options['local']) && $options['local']){
		scheduleTasks();
	}
	

	return $options;
}, 10, 3);