<?php
namespace SIM\USERMANAGEMENT;
use SIM;

//Shortcode for adding user accounts
add_shortcode('create_user_account', function (){
	wp_enqueue_script( 'sim_user_management');

	$user = wp_get_current_user();
	if ( in_array('usermanagement', $user->roles)){
		ob_start();
		?>
		<div class="tabcontent">
			<form class='sim_form' data-reset="true">
				<p>Please fill in the form to create an user account</p>
				
				<label>
					<h4>First name<span class="required">*</span></h4>
					<input type="text"  class='wide' name="first_name" value="" required>
				</label>
				
				<label>
					<h4>Last name<span class="required">*</span></h4>
					<input type="text" class='wide' name="last_name" required>
				</label>
				
				<label>
					<h4>E-mail<span class="required">*</span></h4>
					<input class='wide' type="email" name="email" required>
				</label>
				
				<label>
					<h4>Valid for<span class="required">*</span></h4>
				</label>
				<select name="validity" class="form-control relation" required>
					<option value="">---</option>
					<option value="1">1 month</option>
					<option value="3">3 months</option>
					<option value="6">6 months</option>
					<option value="12">1 year</option>
					<option value="24">2 years</option>
					<option value="unlimited">Always</option>
				</select>
				<?php
				do_action('sim_after_user_create_form');
				
				echo SIM\addSaveButton('adduseraccount', 'Add user account');
				?>
			</form>
		</div>
		<?php
		
		return ob_get_clean();
	}else{
		return "You have no permission to see this";
	}
});

//Shortcode to display the pending user accounts
add_shortcode('pending_user', function (){
	if ( !current_user_can( 'edit_others_pages' ) ){
		return "No permission!";
	}

	//Delete user account if there is an url parameter for it
	if(isset($_GET['delete_pending_user'])){
		//Get user id from url parameter
		$UserId = $_GET['delete_pending_user'];
		//Check if the user account is still pending
		if(get_user_meta($UserId,'disabled',true) == 'pending'){
			//Load delete function
			require_once(ABSPATH.'wp-admin/includes/user.php');
			
			//Delete the account
			$result = wp_delete_user($UserId);
			if ($result){
				//show succesmessage
				echo '<div class="success">User succesfully removed</div>';
			}
		}
	}
	
	//Activate useraccount
	if(isset($_GET['activate_pending_user'])){
		//Get user id from url parameter
		$UserId = $_GET['activate_pending_user'];
		//Check if the user account is still pending
		if(get_user_meta($UserId,'disabled',true) == 'pending'){
			//Send welcome-email
			wp_new_user_notification($UserId, null, 'user');

			//Make approved
			delete_user_meta( $UserId, 'disabled');

			// run account update hook
			do_action('sim_approved_user', $userId);
			
			echo '<div class="success">Useraccount succesfully activated</div>';
		}
	}
	
	//Display pening user accounts
	$list = '';
	//Get all the users who need approval
	$pendingUsers = get_users(array(
		'meta_key'     => 'disabled',
		'meta_value'   => 'pending',
		'meta_compare' => '=',
	));
	
	// Array of WP_User objects.
	if ( $pendingUsers ) {
		foreach ( $pendingUsers as $pendingUser ) {
			$approveUrl	 = add_query_arg( 'activate_pending_user', $pendingUser->ID);
			$deleteUrl	 = add_query_arg( 'delete_pending_user', $pendingUser->ID);
			$list		.= "<li>$pendingUser->display_name  ($pendingUser->user_email) <a href='$approveUrl'>Approve</a>   <a href='$deleteUrl'>Delete</a></li>";
		}
	}else{
		return "<p>There are no pending user accounts.</p>";
	}
	
	if (!empty($list)){
		$html 	 = "<p>";
			$html	.= "<strong>Pending user accounts:</strong><br>";
			$html	.= "<ul>";
				$html	.= $list;
			$html	.="</ul>";
		$html	.= "</p>";
		return $html;
	}
});

//Shortcode to display number of pending user accounts
add_shortcode('pending_user_icon',function (){
	$pendingUsers = get_users(array(
		'meta_key'     => 'disabled',
		'meta_value'   => 'pending',
		'meta_compare' => '=',
	));
	
	if (count($pendingUsers) > 0){
		return '<span class="numberCircle">'.count($pendingUsers).'</span>';
	}
});
