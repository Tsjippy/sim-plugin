<?php
namespace SIM;

//Shortcode for adding temporary user accounts
add_shortcode('create_temp_user','SIM\create_temp_user');
function create_temp_user($atts){
	$user = wp_get_current_user();
	if ( in_array('usermanagement',$user->roles)){		
		//Load js
		wp_enqueue_script('sim_other_script');
		
		ob_start();
		?>
		<div class="tabcontent">
			<form class='sim_form' data-reset="true">
				<p>Please fill in the form to create an user account</p>
				
				<input type="hidden" name="action" value="adduseraccount">
				<input type="hidden" name="create_user_nonce" value="<?php echo wp_create_nonce("create_user_nonce");?>">
				
				<label>
					<h4>First name<span class="required">*</span></h4>
					<input type="text" name="first_name" value="" required>
				</label>
				
				<label>
					<h4>Last name<span class="required">*</span></h4>
					<input type="text" class="" name="last_name" required>
				</label>
				
				<label>
					<h4>E-mail<span class="required">*</span></h4>
					<input class="" type="email" name="email" required>
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
				echo add_save_button('adduseraccount', 'Add user account');
			echo '</form>';
		echo '</div>';
		
		return ob_get_clean();
	}else{
		return "You have no permission to see this";
	}
}

//Make the adduseraccount function available via AJAX for logged-in users
add_action ( 'wp_ajax_adduseraccount', function(){
	if(!is_user_logged_in()) wp_die('Why do you try to hack me?');

	$last_name	= sanitize_text_field($_POST["last_name"]);
	$first_name = sanitize_text_field($_POST["first_name"]);

	if(empty($last_name)) wp_die( 'Please fill in a last name!',500);
	if(empty($first_name)) wp_die('Please fill in a first name!',500);

	//Check if form to add a family member is submitted
	verify_nonce('create_user_nonce');
	
	//Get the post data
	$email = !empty($_POST["email"]) ? sanitize_email($_POST["email"]) : null;

	if ($email == null){
		$username = get_available_username();
		
		//Make up a non-existing emailaddress
		$email = sanitize_email($username."@".$last_name.".empty");
	}
	
	$user 		= wp_get_current_user();
	$user_roles = $user->roles;
	if(in_array('usermanagement', $user_roles)){
		$approved = true;
	}
	
	if(!empty($_POST["validity"])){
		$validity = $_POST["validity"];
	}else{
		$validity = "unlimited";
	}
	
	//Create the account
	$user_id = add_user_account($first_name, $last_name, $email, $approved, $validity);
	
	if(is_numeric($user_id)){	
		//Add to mailchimp
		$Mailchimp = new MAILCHIMP\Mailchimp($user_id);
		$Mailchimp->add_to_mailchimp();
		
		//Store the validity
		if(!empty($_POST["validity"])){
			update_user_meta($user_id,"validity",$_POST["validity"]);
		}
	
		if(in_array('usermanagement', $user_roles)){
			$url = get_site_url()."/update-personal-info/?userid=$user_id";
			$message = "Succesfully created an useraccount for $first_name<br>You can edit the deails <a href='$url'>here</a>";
		}else{
			$message =  "Succesfully created useraccount for $first_name<br>You can now select $first_name in the dropdowns";
		}
		
		wp_die(json_encode(
			[
				'message'	=> $message,
				'id'		=> $user_id,
				'callback'	=> 'add_new_relation_to_select'
			]
		));
	}else{
		wp_die('Creating user account failed',500);
	}
});

//Helper function for the ajax_add_user_account function
function add_user_account($first_name, $last_name, $email, $approved = false, $validity = 'unlimited'){
	//Get the username based on the first and lastname
	$username = get_available_username($first_name, $last_name);// function in registration_fields.php
	
	//Build the user
	$userdata = array(
		'user_login'    => $username,
		'last_name'     => $last_name,
		'first_name'    => $first_name,
		'user_email'    => $email,
		'display_name'  => "$first_name $last_name",
		'user_pass'     => NULL
	);
	
	//Give it the guest user role
	if($validity != "unlimited"){
		$userdata['role'] = 'subscriber';
	}
	//Insert the user
	$user_id = wp_insert_user( $userdata ) ;
	
	if(is_wp_error($user_id)){
		print_array($user_id->get_error_message());
		wp_die($user_id->get_error_message(),500);
	}
	
	if($approved == false){
		print_array('not approved');
		//Make the useraccount inactive
		update_user_meta( $user_id, 'disabled', 'pending');
	}else{
		delete_user_meta( $user_id, 'disabled');
		wp_send_new_user_notifications($user_id);
	}

	//Store the validity
	update_user_meta( $user_id, 'account_validity',$validity);
	
	//Force an account update
	do_action( 'profile_update', $user_id, get_userdata($user_id));
	
	// Return the user id
	if ( ! is_wp_error( $user_id ) ) {
		return $user_id;
	}else{
		return 'error: '.$user_id->get_error_message();
	}
}

add_action('user_register', function($user_id, $user){
	new_user_notification_email( ['subject'=>'','message'=>''], get_userdata($user_id), get_bloginfo( 'name' ) );
},10,2);


//Shortcode to display the pending user accounts
add_shortcode('pending_user',function ($atts){
	if ( current_user_can( 'edit_others_pages' ) ) {
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
				if ($result == true){
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
				//Make approved
				delete_user_meta( $UserId, 'disabled');
				//Send welcome-email
				wp_new_user_notification($UserId, null, 'user');
				echo '<div class="success">Useraccount succesfully activated</div>';
			}
		}
		
		//Display pening user accounts
		$initial_html = "";
		$html = $initial_html;
		//Get all the users who need approval
		$pending_users = get_users(array(
			'meta_key'     => 'disabled',
			'meta_value'   => 'pending',
			'meta_compare' => '=',
		));
		
		// Array of WP_User objects.
		if ( $pending_users ) {
			$html .= "<p><strong>Pending user accounts:</strong><br><ul>";
			foreach ( $pending_users as $pending_user ) {
				$userdata = get_userdata($pending_user->ID);
				$approve_url = add_query_arg( 'activate_pending_user', $pending_user->ID);
				$delete_url = add_query_arg( 'delete_pending_user', $pending_user->ID);
				$html .= '<li>'.$pending_user->display_name.'  ('.$userdata->user_email.') <a href="'.$approve_url.'">Approve</a>   <a href="'.$delete_url.'">Delete</a></li>';
			}
		}else{
			return "<p>There are no pending user accounts.</p>";
		}
		
		if ($html != $initial_html){
			$html.="</ul></p>";
			return $html;
		}
	}
});

//Shortcode to display number of pending user accounts
add_shortcode('pending_user_icon',function ($atts){
	$pending_users = get_users(array(
		'meta_key'     => 'disabled',
		'meta_value'   => 'pending',
		'meta_compare' => '=',
	));
	
	if (count($pending_users) > 0){
		return '<span class="numberCircle">'.count($pending_users).'</span>';
	}
});