<?php
namespace SIM\USERMANAGEMENT;
use SIM;

function display_roles($userId){
	global $wp_roles;

	wp_enqueue_script( 'sim_user_management');
	
	//Get the roles this user currently has
	$roles = get_userdata($userId)->roles;				
	//Get all available roles
	$user_roles = $wp_roles->role_names;
	
	//Remove these roles from the roles array
	if(!in_array('administrator',(array)$roles)) 	unset($user_roles['administrator']);
	
	//Sort the roles
	asort($user_roles);

	ob_start();
	//Content
	?>
	<style>
		.infobox{
			margin-top: -20px;
		}

		.info-icon-wrapper{
			margin-bottom: 10px;
		}

		.info_icon{
			margin-bottom:0px;
			position: absolute;
			right: 10px;
			max-width: 20px;
		}

		div .infobox .info_text{
			position: absolute;
    		right: 40px;
			bottom: unset;
		}
	</style>

	<div class="role_info">
		<form>
			<input type='hidden' name='userid' value='<?php echo $userId;?>'>
			<h3>Select user roles</h3>
			<p>
				Select the roles this user should have.<br>
				If you want to disable a user go to the login info tab.
			</p>
			<?php
		foreach($user_roles as $key=>$role_name){
			$checked = '';
			if(in_array($key,(array)$roles))	$checked = 'checked';
			?>
			<label> 
				<input type='checkbox' name='roles[<?php echo $key;?>]' value='<?php echo $role_name;?>' <?php echo $checked;?>>
				<?php 
				echo $role_name;
				?>
			<div class="infobox">
				<div class="info-icon-wrapper">
					<p class="info_icon">
						<img draggable="false" role="img" class="emoji" alt="ℹ" src="<?php echo PICTURESURL;?>/info.png">
					</p>
				</div>
				<span class="info_text">
					<?php
					echo $role_name.' - <i>'.apply_filters('sim_role_description', '', $key).'</i>';
					?>
				</span>
			</div>
			</label>
			<br>
			<?php
		}
		
		echo SIM\add_save_button('updateroles','Update roles');
	
		?>
		</form>
	</div>
	<?php
	return ob_get_clean();
}