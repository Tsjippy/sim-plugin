<?php
namespace SIM;

//Shortcode for adding temporary user accounts
add_shortcode('create_temp_user','SIM\create_temp_user');
function create_temp_user($atts){
	$user = wp_get_current_user();
	if ( in_array('usermanagement',$user->roles)){		
		//Load js
		wp_enqueue_script('simnigeria_forms_script');
		
		ob_start();
		?>
		<div class="tabcontent">
			<form class='simnigeria_form' data-reset="true">
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
		$Mailchimp = new Mailchimp($user_id);
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
		//Make the useraccount unapproved
		update_user_meta( $user_id, 'wp-approve-user', "");
		update_user_meta( $user_id, 'wp-approve-user-new-registration',true);
	}else{
		delete_user_meta( $user_id, 'wp-approve-user');
		delete_user_meta( $user_id, 'wp-approve-user-new-registration');
		update_user_meta( $user_id, 'wp-approve-user',true);
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