<?php
namespace SIM;

function display_roles($user_id){
	global $wp_roles;
	
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
			<input type="hidden" name="action" value = "updateroles">
			<input type='hidden' name='change_roles' value='<?php echo wp_create_nonce( 'change_roles');?>'>
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
		
		echo add_save_button('updateroles','Update roles');
	
		?>
		</form>
	</div>
	<?php
	return ob_get_clean();
}

//Make updateroles function availbale for AJAX request
add_action ( 'wp_ajax_updateroles', function(){
	if (isset($_POST['userid']) and is_numeric($_POST['userid'])){
		verify_nonce('change_roles');
		
		$user 			= get_userdata($_POST['userid']);
		$user_roles 	= $user->roles;
		$new_roles		= (array)$_POST['roles'];
		
		//Check if new roles require mailchimp actions
		$Mailchimp = new MAILCHIMP\Mailchimp($user->ID);
		$Mailchimp->role_changed($new_roles);
		
		//add new roles
		foreach($new_roles as $key=>$role){
			//If the role is set, and the user does not have the role currently
			if(!in_array($key,$user_roles)){
				$user->add_role( $key );
				print_array("Added role '$role' for user {$user->display_name}");
			}
		}
		
		foreach($user_roles as $role){
			//If the role is not set, but the user has the role currently
			if(!in_array($role,array_keys($new_roles))){
				$user->remove_role( $role );
				print_array("Removed role '$role' for user {$user->display_name}");
			}
		}
		
		wp_die("Updated roles succesfully");
	}else{
		wp_die("Invalid user id given",500);
	}
});