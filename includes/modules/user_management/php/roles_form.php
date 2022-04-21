<?php
namespace SIM\USERMANAGEMENT;
use SIM;

function display_roles($user_id){
	global $wp_roles;

	wp_enqueue_script( 'sim_user_management');
	
	//Get the roles this user currently has
	$roles = get_userdata($user_id)->roles;				
	//Get all available roles
	$user_roles = $wp_roles->role_names;
	
	//Remove these roles from the roles array
	unset($user_roles['administrator']);
	unset($user_roles['author']);
	unset($user_roles['contributor']);
	unset($user_roles['editor']);
	
	//Sort the roles
	asort($user_roles);

	ob_start();
	//Content
	?>
	<div class="role_info">
		<form>
			<input type='hidden' name='userid' value='<?php echo $user_id;?>'>
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
				<?php echo $role_name;?>
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