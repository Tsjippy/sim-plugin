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

	$local	= SIM\getModuleOption(MODULE_SLUG, 'local');

	if($local){
		if(strpos(php_uname(), 'Linux') !== false){
			$signal = new SignalBus();
		}else{
			$signal = new Signal();
		}
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
		echo "<div class='error'>$signal->error<br><br></div>";
	}

	?>
	<h4>Connection details</h4>
	<p>
		Currently connected to <?php echo $signal->phoneNumber; ?>
		<a href='<?php echo $url;?>&unregister=true' class='button'>Unregister</a><br>
	</p>

	<label for="reminder_freq">How often should people be reminded to add a signal website to the website</label>
	<br>
	<select name="reminder_freq">
		<?php
		SIM\ADMIN\recurrenceSelector($settings['reminder_freq']);
		?>
	</select>

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

		?>
		<div class="">
			<h4>Select optional Signal group(s) to invite new users to:</h4>
			<?php

			if(empty($settings['invgroups'])){
				$settings['invgroups']	= [];
			}

			foreach($signalGroups as $group){
				if(empty($group->name)){
					continue;
				}
				?>
				<label>
					<input type='checkbox' name='invgroups[]' value='<?php echo $group->path;?>' <?php if(in_array($group->path, $settings['invgroups'])){echo 'checked';}?>>
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
		if(strpos(php_uname(), 'Linux') !== false){
			$signal = new SignalBus();
			$signal->createDbTable();
		}else{
			$signal = new Signal();
		}

		$signal->checkPrerequisites();

		if(empty(shell_exec('javac -version'))){
			echo "<div class='warning'>";
				echo "Java JDK is not installed.<br>You need to install Java JDK.<br>";
			echo "</div>";
		}else{
			if($signal->phoneNumber){
				connectedOptions($signal, $settings);
			}else{
				notConnectedOptions();
			}
		}
	}else{
		notLocalOptions($settings);
	}

	return ob_get_clean();
}, 10, 3);

add_filter('sim_module_data', function($dataHtml, $moduleSlug, $settings){
	global $wpdb;

	//module slug should be the same as grandparent folder name
	if($moduleSlug != MODULE_SLUG){
		return $dataHtml;
	}

	ob_start();

	$local	= false;
	if(isset($settings['local']) && $settings['local']){
		$local	= true;
	}

	if(!$local){
		return '';
	}

	$amount	= 100;
	if(isset($_REQUEST['amount'])){
		$amount	= $_REQUEST['amount'];
	}

	$months	= 3;
	if(isset($_REQUEST['months'])){
		$months	= $_REQUEST['months'];
	}

	$page	= 1;
	if(isset($_REQUEST['nr'])){
		$page	= $_REQUEST['nr'];
	}

	$signal 	= new SignalBus();
	$messages	= $signal->getMessageLog($amount, $page, strtotime("-$months months", time()));

	if(empty($messages)){
		return '';
	}

	?>
	<div class='send-signal-messages'>
        <h2>Message History</h2>

		<form method='get' id='message-overview-settings'>
			<label>
				Show Messages from the last <input type='number' name='months' value='<?php echo $months ;?>' style='max-width: 60px;'> months only
			</label>
			<br>
			<label>
				Amount of messages to show <input type='number' name='amount' value='<?php echo $amount; ?>' style='max-width: 60px;'>
			</label>
			<br>
			<input type='submit' value='Apply'>
		</form>

		<?php

		if($page > 1){
			$url		= admin_url("admin.php?page=sim_signal&tab=data&amount=$amount&months=$months&nr=");
			$totalPages	= ceil($signal->totalMessages/$amount);
	
			$start	= max($page - 2, 1);
			$end	= min($page + 2, $totalPages);
			
			echo "<a href='{$url}1'>< First</a>   ";
	

			for ($x = $start; $x <= $end; $x++) {
				echo "   <a href='$url$x'>$x</a>   ";
			}

			if($page < $totalPages){
				echo "   <a href='$url$totalPages'>Last ></a>";
			}
		}

		?>

        <table class='statistics_table'>
			<thead>
				<th>Date</th>
				<th>Time</th>
				<th>Recipient</th>
				<th>Message</th>
			</thead>
            <tbody>
				<?php
					foreach($messages as $message){
						$date		= date('d-m-Y', $message->timesend);
						$time		= date('H:i', $message->timesend);

						$recipient	= '';
						if(strpos($message->recipient, '+') !== false){
							$recipient	= $wpdb->get_var("SELECT display_name FROM $wpdb->users WHERE ID in (SELECT user_id FROM `{$wpdb->prefix}usermeta` WHERE `meta_value` LIKE '%$message->recipient%')");
						}else{
							$signal->listGroups();
							foreach($signal->groups as $group){
								if($group->id == $message->recipient){
									$recipient	= $group->name;
									break;
								}
							}
						}

						?>
						<tr>
							<td class='date'><?php echo $date;?></td>
							<td class='time'><?php echo $time?></td>
							<td class='recipient'><?php echo $recipient;?></td>
							<td class='message'><?php echo $message->message;?></td>
						</tr>
						<?php
					}
				?>
            </tbody>
        </table>
    </div>

	<?php
	return ob_get_clean();
}, 10, 3);

add_filter('sim_module_functions', function($dataHtml, $moduleSlug, $settings){
	//module slug should be the same as grandparent folder name
	if($moduleSlug != MODULE_SLUG){
		return $dataHtml;
	}

	// check if we need to send a message
	if(!empty($_POST['challenge']) && !empty($_POST['captchastring'])){
		$signal	= new SignalBus();
		$result	= $signal->submitRateLimitChallenge($_POST['challenge'], $_POST['captchastring']);
		echo $result;
    }

	// check if we need to send a message
	if(!empty($_POST['message']) && !empty($_POST['recipient'])){
        $result	= sendSignalMessage($_POST['message'], $_POST['recipient']);
		echo $result;
    }

	$phonenumbers	= '';
	foreach (get_users() as $user) {
		$phones	= (array)get_user_meta($user->ID, 'phonenumbers', true);
		foreach($phones as $phone){
			$phonenumbers	.= "<option value='$phone'>$user->display_name ($phone)</option>";
		}
	}

	ob_start();

	if(isset($_REQUEST['challenge']) && !isset($_REQUEST['captchastring'])){
		?>
		<form method='post'>
			<label>
				<h4>Challenge string</h4>
				<input type='text' name='challenge' value='<?php echo $_REQUEST['challenge'];?>' style='width:100%;' required>
			</label>

			<h4>Captcha string</h4>
			<textarea name='captchastring' style='width:100%;' required rows=10></textarea>

			<br>

			<button>Submit</button>
		</form>

		<?php
		return ob_get_clean();
	}

	?>
	<form method='post'>
		<label>
			<h4>Message to be send</h4>
			<textarea name='message' style='width:100%;' required></textarea>
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
		<button>Send message</button>
	</form>
	<br>
	<br>

	<?php

	return ob_get_clean();
}, 10, 3);

add_filter('sim_email_settings', function($optionsHtml, $moduleSlug, $settings){
	//module slug should be the same as grandparent folder name
	if($moduleSlug != MODULE_SLUG || !SIM\getModuleOption(MODULE_SLUG, 'local')){
		return $optionsHtml;
	}

	ob_start();

	?>
	<label>
		Define the e-mail people get when they should submit a Signal phonenumber
	</label>
	<br>

	<?php
	$email    = new SignalEmail(wp_get_current_user());
	$email->printPlaceholders();
	?>

	<h4>E-mail to remind people to add their Signal phonenumber</h4>
	<?php

	$email->printInputs($settings);

	return ob_get_clean();
}, 10, 3);

add_filter('sim_module_updated', function($options, $moduleSlug){
	//module slug should be the same as grandparent folder name
	if($moduleSlug != MODULE_SLUG){
		return $options;
	}

	scheduleTasks();

	return $options;
}, 10, 2);