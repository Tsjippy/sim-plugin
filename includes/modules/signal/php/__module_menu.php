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
		<br>
		<label>
			Phone number you want to register
			<input type="tel" name="phone" pattern="\+[0-9]{9,}" title="Phonenumber starting with a +. Only numbers. Example: +2349041234567" style='width:100%'>
		</label>
		<br>
		<label>
			Captcha:
			<br>
			<input type='text' name='captcha' style='width:100%' required>
		</label>
		Get a captcha from <a href='https://signalcaptchas.org/registration/generate.html' target=_blank>here</a>, for an explanation see <a href='https://github.com/AsamK/signal-cli/wiki/Registration-with-captcha' target=_blank>the manual.</a>
		<br>
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

	if(strpos(php_uname(), 'Linux') !== false){
		$signal = new SignalBus();
	}else{
		$signal = new Signal();
	}

	if(isset($_GET['unregister'])){
		$signal->unregister();

	}elseif(isset($_GET['register'])){
		registerForm();
	}elseif(!empty($_POST['captcha'])){
		echo $signal->register($_POST['phone'], $_POST['captcha'], isset($_POST['voice']));

		if(!empty($signal->error)){
			registerForm();
		}else{
			?>
			<form method='post'>
				You should have received a verification code.<br>
				Please insert the code below.
				<br>
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
		echo $signal->verify($_POST['verification-code']);
		if(empty($signal->error)){
			unset($_POST['verification-code']);

			echo "<div class='success'>Succesfully registered with Signal!</div>";
		}else{
			registerForm();
		}
	}elseif(isset($_GET['link'])){
		echo $signal->link();
	}
});

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
	<h4>Connection details</h4>
	<p>
		Currently connected to <?php echo $signal->phoneNumber; ?>
		<a href='<?php echo $url;?>&unregister=true' class='button'>Unregister</a><br>
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
function notConnectedOptions(){
	$url		= admin_url( "admin.php?page={$_GET['page']}&tab={$_GET['tab']}" );

	?>
	<h4>Connection details</h4>
	<p>
		Currently not connected to Signal
		<br>
		<a href='<?php echo $url;?>&register=true' class='button'>Register a new number with Signal</a>
		
		<a href='<?php echo $url;?>&link=true' class='button'>Link to an existing Signal number</a>
	</p>
	<?php
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
	//module slug should be the same as grandparent folder name
	if($moduleSlug != MODULE_SLUG || isset($_GET['register']) || $_POST['captcha'] || $_POST['verification-code']){
		return $optionsHtml;
	}
	$local	= false;
	if(isset($settings['local']) && $settings['local']){
		$local	= true;
	}

	ob_start();

	?>
	<strong>Server type</strong><br>
	Indicate if you can install signal-cli on this server or not<br>
	I have root access on this server
	<label class="switch">
		<input type="checkbox" name="local" value=1 <?php if($local){echo 'checked';}?>>
		<span class="slider round"></span>
	</label>
	<br>
	<br>
	<?php
	
	if($local){
		?>
		<strong>D-bus type</strong><br>
		Indicate if you want a system or a session dbus<br>
		<label>
			<input type="radio" name="dbus-type" value='system' <?php if(isset($settings['dbus-type']) && $settings['dbus-type'] == 'system'){echo 'checked';}?>>
			System
		</label>
		<label>
			<input type="radio" name="dbus-type" value='system' <?php if(isset($settings['dbus-type']) && $settings['dbus-type'] == 'session'){echo 'checked';}?>>
			Session
		</label>
		<br>
		System has the disadvantage that any user on the server has access to your signal information (although not easy)<br>
		Session has the disadvantage that only one site in your userprofile can receive messages.<br>
		<?php

		if(isset($settings['dbus-type'])){
			if(empty(shell_exec('javac -version'))){
				echo "<div class='warning'>";
					
					if(strpos(php_uname(), 'Linux') !== false){
						if( $settings['dbus-type'] == 'session'){
							echo "Java JDK is not installed.<br>You need to install Java JDK with this command:<br><code>sudo apt install openjdk-17-jdk -y</code>";
						}else{
							echo "<strong>Signal-cli not yet installed</strong><br>";
							echo "Please install signal-cli with this command:<br>";
							echo "<code>sudo bash ".MODULE_PATH."daemon/install.sh '".WP_CONTENT_DIR."/signal-cli/program' '".MODULE_PATH."daemon'</code><br><br>";
						}
					}else{
						echo "Java JDK is not installed.<br>You need to install Java JDK.<br>";
					}
				echo "</div>";
			}else{
				if(strpos(php_uname(), 'Linux') !== false){
					$signal = new SignalBus();
				}else{
					$signal = new Signal();
				}

				if($signal->phoneNumber){
					connectedOptions($signal, $settings);
				}else{
					notConnectedOptions($settings);
				}
			}
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
					if(strpos(php_uname(), 'Linux') !== false){
						$signal = new SignalBus();
					}else{
						$signal = new Signal();
					}

					$groups	= $signal->listGroups();

					foreach($groups as $group){
						if(empty($group->name)){
							continue;
						}
						echo "<option value='$group->id'>$group->name</option>";
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