<?php
namespace SIM;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

//Add a shortcode for the username
add_shortcode( 'username', function ( $atts ) {
	if (is_user_logged_in()){
		$current_user = wp_get_current_user();
		return $current_user->user_login;
	}else{
		return "visitor";	
	}
} );

//Add a shortcode for the displayname
add_shortcode( 'displayname', function ( $atts ) {
	if (is_user_logged_in()){
		$current_user = wp_get_current_user();
		return $current_user->first_name;
	}else{
		return "visitor";	
	}
});

//Shortcode to return the amount of loggins in words
add_shortcode("login_count",function ($atts){
	$UserID = get_current_user_id();
	$current_loggin_count = get_user_meta( $UserID, 'login_count', true );
	//Get the word from the array
	if ($current_loggin_count != "" and $current_loggin_count < 20){
		global $num_word_list;
		return $num_word_list[$current_loggin_count];
	//Just return the number
	}elseif ($current_loggin_count != "" and $current_loggin_count > 19){
		return strval($current_loggin_count);
	//key not set, assume its the first time
	}else{
		return "your first";
	}
});

//Shortcode for the welcome message on the homepage
add_shortcode("welcome",function ($atts){
	if (is_user_logged_in()){
		global $WelcomeMessagePageID;
		$UserID = get_current_user_id();
		//Check welcome message needs to be shown
		$show_welcome = get_user_meta( $UserID, 'welcomemessage', true );
		if ($show_welcome == ""){
			$welcome_post = get_post($WelcomeMessagePageID); 
			if($welcome_post != null){
				//Load js
				wp_enqueue_script('simnigeria_message_script');
				
				//Html
				$html = '<div id="welcome-message">';
				$html .= '<h4>'.$welcome_post->post_title.'</h4>';
				$html .= apply_filters('the_content',$welcome_post->post_content);
				$html .= '<button type="button" class="button" id="welcome-message-button">Do not show again</button></div>';
				return $html;
			}
		}
	}
});

//Shortcode to display the pending user accounts
add_shortcode('pending_user',function ($atts){
	if ( current_user_can( 'edit_others_pages' ) ) {
		//Delete user account if there is an url parameter for it
		if(isset($_GET['delete_pending_user'])){
			//Get user id from url parameter
			$UserId = $_GET['delete_pending_user'];
			//Check if the user account is still pending
			if(get_user_meta($UserId,'wp-approve-user-new-registration',true)==1){
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
			if(get_user_meta($UserId,'wp-approve-user-new-registration',true)==1){
				//Make approved
				update_user_meta( $UserId, 'wp-approve-user-mail-sent', true );
				update_user_meta( $UserId, 'wp-approve-user', true );
				delete_user_meta( $UserId, 'wp-approve-user-new-registration');
				//Send welcome-email
				wp_new_user_notification($UserId,null,'user');
				echo '<div class="success">Useraccount succesfully activated</div>';
			}
		}
		
		//Display pening user accounts
		$initial_html = "";
		$html = $initial_html;
		//Get all the users who need approval
		$pending_users = get_users(array(
			'meta_key'     => 'wp-approve-user',
			'meta_value'   => false,
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
		'meta_key'     => 'wp-approve-user',
		'meta_value'   => false,
		'meta_compare' => '=',
	));
	
	if (count($pending_users) > 0){
		return '<span class="numberCircle">'.count($pending_users).'</span>';
	}
});

//Shortcode for expired 2FA grace periods
add_shortcode('expired_2FA',function ($atts){
	$user = wp_get_current_user();
	
	if ( in_array('usermanagement',$user->roles)){
		if(isset($_GET['action']) and isset($_GET['user_id']) and isset($_GET['wp_2fa_nonce'])){
			if($_GET['action'] == 'unlock_account' and is_numeric($_GET['user_id']) and wp_verify_nonce( $_GET['wp_2fa_nonce'], 'wp-2fa-unlock-account-nonce')){
				 reset_2fa($_GET['user_id']);
			}
		}
		//Display user accounts
		$initial_html = "<p><strong>Expired 2FA user accounts:</strong><br><ul>";
		$html = $initial_html;
		//Get all the users with the wp_2fa_user_grace_period_expired meta key
		$expired_2fa_users = get_users( 'meta_key=wp_2fa_user_grace_period_expired' );
		// Array of WP_User objects.
		if ( $expired_2fa_users ) {
			foreach ( $expired_2fa_users as $expired_2fa_user ) {
				//only show users with a valid email
				if (strpos($expired_2fa_user->user_email,'.empty') === false){
					//Build the unlock url
					$url = add_query_arg(
						array(
							'action'       => 'unlock_account',
							'user_id'      => $expired_2fa_user->ID,
							'wp_2fa_nonce' => wp_create_nonce( 'wp-2fa-unlock-account-nonce' ),
						)
					);
					//Add the unlock url html
					$html .= '<li>'.$expired_2fa_user->display_name.'<a href="'.esc_url( $url ).'"> Unlock useraccount</a></li>';
				}
			}
		}
		
		if ($html != $initial_html){
			$html.="</ul></p>";
			return $html;
		}
	}
});

//Shortcode to display number of expired 2FA grace periods
add_shortcode('expired_2fa_icon',function ($atts){
	global $wpdb;
	$count = $wpdb->get_var("SELECT COUNT(user_id) FROM ".$wpdb->prefix ."usermeta WHERE meta_key = 'wp_2fa_user_grace_period_expired' AND user_id in (SELECT ID FROM ".$wpdb->prefix ."users WHERE user_email NOT LIKE '%.empty')");
	
	if ($count>0){
		return '<span class="numberCircle">'.$count.'</span>';
	}
});

//Shortcode to download all contact info
add_shortcode("all_contacts",function (){
	global $post;
	//Make vcard
	if (isset($_GET['vcard'])){
		if($_GET['vcard']=="all"){
			ob_end_clean();
			//ob_start();
			header('Content-Type: text/x-vcard');
			header('Content-Disposition: inline; filename= "SIMContacts.vcf"');
			$vcard = "";
			$users = get_missionary_accounts(false,true,true,['ID']);
			foreach($users as $user){
				$vcard .= build_vcard($user->ID);
			}
			echo $vcard;
		}elseif($_GET['vcard']=="outlook"){
			$zip = new \ZipArchive;
			
			if ($zip->open('SIMContacts.zip', \ZipArchive::CREATE) === TRUE){
				//Get all user accounts
				$users = get_missionary_accounts(false,true,true,['ID','display_name']);
				
				//Loop over the accounts and add their vcards
				foreach($users as $user){
					$zip->addFromString($user->display_name.'.vcf', build_vcard($user->ID));
				}	
			 
				// All files are added, so close the zip file.
				$zip->close();
			}
	
			ob_end_clean();
			
			header('Content-Type: application/zip');
			header('Content-Disposition: inline; filename= "SIMContacts.zip"');
			readfile('SIMContacts.zip');
			
			//remove the zip from the server
			unlink('SIMContacts.zip');
		}
		//echo ob_get_contents();
		die();
	//Return vcard hyperlink
	}else{
		$url 			= add_query_arg( ['vcard' => "all"], get_permalink( $post->ID ) );
		$all_button 	= '<a href="'.$url.'" class="button sim vcard">Gmail and others</a>';
		
		$url 			= add_query_arg( ['vcard' => "outlook"], get_permalink( $post->ID ) );
		$outlook_button	= '<a href="'.$url.'" class="button sim vcard">Outlook</a>';
		
		$html = "<div class='download contacts'>";
		$html .= "<p>If you want to add the contact details of all SIM Nigeria missionaries to your addressbook, you can use one of the buttons below.<br>";
		$html .= "For gmail and other programs you can just import the vcf file.	";
		$html .= "For outlook you receive a zip file. Extract it, then click on each .vcf file to add it to your outlook.</p>";
		$html .= "$outlook_button $all_button";
		$html .= "<p>Be patient, preparing the download can take a while. </p>";
		$html .= "</div>";
		
		return $html;
	}
});

add_shortcode( 'content_filter', function ( $atts = array(), $content = null ) {
	$a = shortcode_atts( array(
        'inversed' => false,
		'roles' => "All",
    ), $atts );
	$inversed 		= $a['inversed'];
	$allowed_roles 	= explode(',',$a['roles']);
	$return = false;
	
    //Get the current user
	$user = wp_get_current_user();
	
	//User is logged in
	if(is_user_logged_in()){
		if( in_array('All',$allowed_roles) or array_intersect($allowed_roles, $user->roles)) { 
			// display content
			$return = true;
		}
	}
    
	//If inversed
	if($inversed){
		//Swap the outcome
		$return = !$return;
	}
	
	//If return is true
	if($return == true){
		//return the shortcode content
		return do_shortcode($content);
	}
});

//Shortcode for financial items
add_shortcode("account_statements",function (){
	global $current_user;
	
	if(isset($_GET["id"])){
		$user_id = $_GET["id"];
	}else{
		$user_id = $current_user->ID;
	}
	$account_statements = get_user_meta($user_id, "account_statements", true);
	
	if(is_child($user_id) == false and is_array($account_statements)){
		//Load js
		wp_enqueue_script('simnigeria_account_statements_script');
		
		$html = "<div class='account_statements'>";
		$html .= '<h3>Account statements</h3>';
		ksort($account_statements);
		$html .= '<table id="account_statements"><tbody>';
		foreach($account_statements as $year=>$month_array){
			if(date("Y") == $year){
				$button_text 	= "Hide $year";
				$visibility 	= '';
			}else{
				$button_text 	= "Show $year";
				$visibility 	= ' style="display:none;"';
			}
				
			$html .= "<button type='button' class='statement_button button' data-target='_$year' style='margin-right: 10px; padding: 0px 10px;'>$button_text</button>";
			if(is_array($month_array)){
				$month_count = count($month_array);
				$first_month = array_key_first($month_array);
				foreach($month_array as $month => $url){
					$site_url	= site_url();
					if(strpos($url, $site_url) === false){
						$url = $site_url.$url;
					}
					
					$html .= "<tr class='_$year'$visibility>";
					if($first_month == $month){
						$html .= "<td rowspan='$month_count'><strong>$year<strong></td>";
					}
					$html .= "<td>
							<a href='$url'>$month</a>
						</td>
						<td>
							<a class='statement' href='$url'>Download</a>
						</td>
					</tr>";
				}
			}
		}
		$html .= '</tbody></table></div>';
		return $html;
	}
});

//Shortcode for vaccination warnings
add_shortcode("expiry_warnings",function (){
	global $PersonnelCoordinatorEmail;
	if(is_numeric($_GET["userid"]) and in_array('usermanagement', wp_get_current_user()->roles )){
		$user_id	= $_GET["userid"];
	}else{
		$user_id = get_current_user_id();
	}
	$remindercount = 0;
	$reminder_html = "";
	
	$visa_info = get_user_meta( $user_id, "visa_info",true);
	if (is_array($visa_info) and isset($visa_info['greencard_expiry'])){
		$reminder_html .= check_expiry_date($visa_info['greencard_expiry'],'greencard');
		if($reminder_html != ""){
			$remindercount = 1;
			$reminder_html .= '<br>';
		}
	}
		
	$vaccination_reminder_html = vaccination_reminders($user_id);
	
	if ($vaccination_reminder_html != ""){
		$remindercount += 1;
		$reminder_html .= $vaccination_reminder_html ;
	}
	
	//Check for children
	$family = get_user_meta($user_id,"family",true);
	//User has children
	if (isset($family["children"])){
		$child_vaccination_reminder_html = "";
		foreach($family["children"] as $key=>$child){
			$result = vaccination_reminders($child);
			if ($result != ""){
				$remindercount += 1;
				$userdata = get_userdata($child);
				$reminder_html .= str_replace("Your",$userdata->first_name."'s",$result);
			}
		}
	}
	
	//Check for upcoming reviews, but only if not set to be hidden for this year
	if(get_user_meta($user_id,'hide_annual_review',true) != date('Y')){
		$personnel_info 				= get_user_meta($user_id,"personnel",true);
		if(is_array($personnel_info) and !empty($personnel_info['review_date'])){
			//Hide annual review warning
			if(isset($_GET['hide_annual_review']) and $_GET['hide_annual_review'] == date('Y')){
				//Save in the db
				update_user_meta($user_id,'hide_annual_review',date('Y'));
				
				//Get the current url withouth the get params
				$url = str_replace('hide_annual_review='.date('Y'),'',current_url());
				//redirect to same page without params
				header ("Location: $url");
			}
			
			$reviewdate	= date('F', strtotime($personnel_info['review_date']));
			//If this month is the review month or the month before the review month
			if($reviewdate == date('F') or date('F', strtotime('-1 month',strtotime($reviewdate))) == date('F')){			
				$generic_documents = get_option('personnel_documents');
				if(is_array($generic_documents) and !empty($generic_documents['Annual review form'])){
					$reminder_html .= "Please fill in the annual review questionary.<br>";
					$reminder_html .= 'Find it <a href="'.get_site_url().'/'.$generic_documents['Annual review form'].'">here</a>.<br>';
					$reminder_html .= 'Then send it to the <a href="mailto:'.$PersonnelCoordinatorEmail.'?subject=Annual review questionary">Personnel coordinator</a><br>';
					$url = add_query_arg( 'hide_annual_review', date('Y'), current_url() );
					$reminder_html .= '<a class="button sim" href="'.$url.'" style="margin-top:10px;">I already send it!</a><br>';
				}
			}
		}
	}
	
	if ($reminder_html != ""){
		$html = '<h3 class="frontpage">';
		if($remindercount > 1){
			$html .= 'Reminders</h3><p>'.$reminder_html;
		}else{
			$reminder_html = str_replace('</li>','',str_replace('<li>',"",$reminder_html));
			$html .= 'Reminder</h3><p>'.$reminder_html;
		}
		
		$html =  '<div id=reminders>'.$html.'</p></div>';
	}
	
	return $html;
});

//Shortcode for recommended fields
add_shortcode("recommended_fields",function ($atts){
	$UserID = get_current_user_id();
	$html	= '';
	$recommendation_html = get_recommended_fields($UserID);
	if (!empty($recommendation_html)){
		$html .=  '<div id=recommendations style="margin-top:20px;"><h3 class="frontpage">Recommendations</h3><p>It would be very helpfull if you could fill in the fields below:</p>'.$recommendation_html.'</div>';
	}
	
	return $html;
});

add_shortcode('missionary_link',function($atts){
	$html = "";
	$a = shortcode_atts( array(
        'id' => '',
		'picture' => false,
		'phone' => false,
		'email' => false,
		'style' => '',
    ), $atts );
	
	$user_id = $a['id'];
	
	if($a['style'] != ''){
		$style = "style='".$a['style']."'";
	}else{
		$style = '';
	}
	
	$html = "<div $style>";
	
	$userdata = get_userdata($user_id);
	$nickname = get_user_meta($user_id,'nickname',true);
	$display_name = "(".$userdata->display_name.")";
	if($userdata->display_name == $nickname) $display_name = '';
	$privacy_preference = get_user_meta( $user_id, 'privacy_preference', true );
	if(!is_array($privacy_preference)) $privacy_preference = [];
	
	$url = get_missionary_page_url($user_id);
	
	if($a['picture'] == true and !isset($privacy_preference['hide_profile_picture'])){
		$profile_picture = display_profile_picture($user_id);
	}
	$html .= "<a href='$url'>$profile_picture $nickname $display_name</a><br>";
	
	if($a['email'] == true){
		$html .= '<p style="margin-top:1.5em;">E-mail: <a href="mailto:'.$userdata->user_email.'">'.$userdata->user_email.'</a></p>';
	}
		
	if($a['phone'] == true){
		$html .= show_phonenumbers($user_id);
	}
	return $html."</div>";
});

add_shortcode("userstatistics",function ($atts){
	wp_enqueue_script('simnigeria_table_script');
	ob_start();
	$users = get_missionary_accounts($return_family=false,$adults=true,$local_nigerians=true);
	?>
	<br>
	<div class='form_table_wrapper'>
		<table class='table' style='max-height:500px;'>
			<thead class='table-head'>
				<tr>
					<th>Name</th>
					<th>Login count</th>
					<th>Last login</th>
					<th>Mandatory pages to read</th>
					<th>Mandatory info to be filled in</th>
					<th>User roles</th>
					<th>Account validity</th>
				</tr>
			</thead>

			<tbody>
				<?php
				foreach($users as $user){
					$login_count= get_user_meta($user->ID,'login_count',true);
					if(!is_numeric(($login_count))) $login_count = 0;
					$last_login_date	= get_user_meta($user->ID,'last_login_date',true);
					if(empty($last_login_date)){
						$last_login_date	= 'Never';
					}else{
						$time_string 	= strtotime($last_login_date);
						if($time_string ) $last_login_date = date('d F Y', $time_string);
					}

					$picture = display_profile_picture($user->ID);

					echo "<tr class='table-row'>";
						echo "<td>$picture {$user->display_name}</td>";
						echo "<td>$login_count</td>";
						echo "<td>$last_login_date</td>";
						echo "<td>".get_must_read_documents($user->ID,true)."</td>";
						echo "<td>".get_required_fields($user->ID)."</td>";
						echo "<td>";
						foreach($user->roles as $role){
							echo $role.'<br>';
						}
						echo "</td>";
						echo "<td>".get_user_meta($user->ID,'account_validity',true)."</td>";
					echo "</tr>";
				}
				?>
			</tbody>
		</table>
	</div>
	<?php
	return ob_get_clean();
});

class Open_SSL {

	const CIPHER_METHOD    = 'aes-256-ctr';
	const BLOCK_BYTE_SIZE  = 16;
	const DIGEST_ALGORITHM = 'SHA256';

	/**
	 * Internal cache var for the PHP ssl functions availability
	 *
	 * @var mixed|boolean
	 *
	 * @since 2.0.0
	 */
	private static $ssl_enabled = null;

	/**
	 * Encrypts given text
	 *
	 * @param string $text - Text to be encrypted.
	 *
	 * @return string
	 *
	 * @since 2.0.0
	 */

	/**
	 * Decrypts crypt text
	 *
	 * @param string $text - Encrypted text to be decrypted.
	 *
	 * @return string
	 *
	 * @since 2.0.0
	 */
	public static function decrypt( string $text ): string {

		if ( self::is_ssl_available() ) {
			$decoded_base = \base64_decode( $text );

			$key = \openssl_digest( \base64_decode( "9bto3O5xU1QYuwWQd/iV+Q==" ), self::DIGEST_ALGORITHM, true );

			$ivlen = \openssl_cipher_iv_length( self::CIPHER_METHOD );

			$iv             = \substr( $decoded_base, 0, $ivlen );
			$ciphertext_raw = \substr( $decoded_base, $ivlen );
			$text           = \openssl_decrypt( $ciphertext_raw, self::CIPHER_METHOD, $key, OPENSSL_RAW_DATA, $iv );
		}

		return $text;
	}

	/**
	 * Generates random bytes by given size
	 *
	 * @param integer $octets - Number of octets for use for random generator.
	 *
	 * @return string
	 *
	 * @since 2.0.0
	 */
	public static function secure_random( int $octets = 0 ): string {
		if ( 0 === $octets ) {
			$octets = self::BLOCK_BYTE_SIZE;
		}

		return \random_bytes( $octets );
	}

	/**
	 * Checks the open ssl methods existence
	 *
	 * @return boolean
	 *
	 * @since 2.0.0
	 */
	public static function is_ssl_available(): bool {
		if ( null === self::$ssl_enabled ) {
			self::$ssl_enabled = false;
			if ( \function_exists( 'openssl_encrypt' ) ) {
				self::$ssl_enabled = true;
			}
		}

		return self::$ssl_enabled;
	}
}

//Shortcode for testing
add_shortcode("test",function ($atts){
	$body = 
	'<!--[if gte mso 9]>
			<img src="cid:logo" width="300" height="400" />
	<![endif]-->
	<img src="cid:logo1" width="300" height="400" />
	<img src="cid:logo2" width="300" height="400" />
	<img src="cid:logo3" width="300" height="400" />
	<img src="data:image/jpeg;base64,/9j/4AAQSkZJRgABAQAAAQABAAetc." />';
	
	
	$file = ABSPATH.'/wp-content/uploads/cropped-S-for-SIM.png';
	$headers = [];
	$headers[]	='Content-Type: text/html; charset=UTF-8';
	example_send_mail( 'enharmsen@gmail.com', 'subject', '<img src="cid:uniqueid"/>', $headers);
});

function example_send_mail( $email, $subject, $body, $headers = '', $attachments = array() ) {
    add_action( 'phpmailer_init', function( &$phpmailer ) {
        $phpmailer->SMTPKeepAlive=true;
        $phpmailer->AddEmbeddedImage( ABSPATH.'/wp-content/uploads/cropped-S-for-SIM.png', 'uniqueid','SIM.png');
    });
    wp_mail( $email, $subject, $body, $headers, $attachments );
}