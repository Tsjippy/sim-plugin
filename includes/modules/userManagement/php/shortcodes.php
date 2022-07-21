<?php
namespace SIM\USERMANAGEMENT;
use SIM;

//Shortcode for adding user accounts
add_shortcode('create_user_account', function (){
	wp_enqueue_script( 'sim_user_management');

	$user = wp_get_current_user();
	if ( in_array('usermanagement',$user->roles)){		
		ob_start();
		?>
		<div class="tabcontent">
			<form class='sim_form' data-reset="true">
				<p>Please fill in the form to create an user account</p>
				
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

//Shortcode for the dashboard
add_action('sim_dashboard_warnings', function($userId){
	$dashboardWarnings	= new DashboardWarnings($userId);

	$dashboardWarnings->greenCardReminder();
		
	$dashboardWarnings->vaccinationReminders();
	
	$dashboardWarnings->reviewReminder();
	
	if (!empty($dashboardWarnings->reminderHtml)){
		$text	= 'Reminders';
		
		if($dashboardWarnings->reminderCount < 2){
			$dashboardWarnings->reminderHtml = str_replace(['</li>','<li>'], '', $dashboardWarnings->reminderHtml);
			$text	= 'Reminder';
		}else{
			//$dashboardWarnings->reminderHtml = str_replace(['</li>','<li>'], '', $dashboardWarnings->reminderHtml);
		}
		
		?>
		<div id=reminders>
			<h3 class='frontpage'><?php echo $text;?></h3>
			<p>
				<?php echo $dashboardWarnings->reminderHtml;?>
			</p>
		</div>
		<?php
	}
});

add_filter('sim_loggedin_homepage',  function($content){
	$content	.= expiryWarnings();
	return $content;
});

//Shortcode for expiry warnings
add_shortcode("expiry_warnings", __NAMESPACE__.'\expiryWarnings');
function expiryWarnings(){
	if(is_numeric($_GET["userid"]) && in_array('usermanagement', wp_get_current_user()->roles )){
		$userId	= $_GET["userid"];
	}else{
		$userId = get_current_user_id();
	}

	$dashboardWarnings	= new DashboardWarnings($userId);

	$dashboardWarnings->greenCardReminder();
		
	$dashboardWarnings->vaccinationReminders();
	
	$dashboardWarnings->reviewReminder();
	
	if (!empty($dashboardWarnings->reminderHtml)){
		$html = '<h3 class="frontpage">';
		if($dashboardWarnings->reminderCount > 1){
			$html 			.= 'Reminders</h3><p>'.$dashboardWarnings->reminderHtml;
		}else{
			$dashboardWarnings->reminderHtml 	= str_replace(['</li>', '<li>'], '', $dashboardWarnings->reminderHtml);
			$html 			.= 'Reminder</h3><p>'.$dashboardWarnings->reminderHtml;
		}
		
		$html 				=  '<div id=reminders>'.$html.'</p></div>';
	}
	
	return $html;
}

//Shortcode for userdata forms
add_shortcode("user-info", __NAMESPACE__.'\userInfoPage');
function userInfoPage($atts){
	if(!is_user_logged_in()){
		if(function_exists('SIM\LOGIN\loginModal')){
			SIM\LOGIN\loginModal("You do not have permission to see this, sorry.");
			return'';
		}

		return "<p>You do not have permission to see this, sorry.</p>";
	}

	wp_enqueue_style('sim_forms_style');
	
	$a = shortcode_atts( array(
		'currentuser' 	=> false,
		'id' 			=> '', 
	), $atts );

	$showCurrentUserData = $a['currentuser'];
	
	//Variables
	$medicalRoles		= ["medicalinfo"];
	$genericInfoRoles 	= array_merge(['usermanagement'], $medicalRoles,['administrator']);
	$user 				= wp_get_current_user();
	$userRoles 			= $user->roles;
	$tabs				= [];
	$html				= '';
	$userAge 			= 19;
	$availableForms		= (array)SIM\getModuleOption(MODULE_SLUG, 'enabled-forms');
	$userSelectRoles	= apply_filters('sim_user_page_dropdown', $genericInfoRoles);

	//Showing data for current user
	if($showCurrentUserData){
		$userId = get_current_user_id();
	//Display a select to choose which users data should be shown
	}elseif(array_intersect($userSelectRoles, $userRoles )){
		$userId	= $a['id'];
		$user	= false;
		
		if(isset($_GET["userid"])){
			$userId	= $_GET['userid'];
		}

		if(is_numeric($userId)){
			$user	= get_userdata($userId);
		}

		if($user){
			$userId = $_GET["userid"];
		}else{
			return SIM\userSelect("Select an user to show the data of:", false, false, '', 'user_selection', [], '', []);
		}

		$userBirthday = get_user_meta($userId, "birthday", true);
		if(!empty($userBirthday)){
			$userAge = date_diff(date_create(date("Y-m-d")), date_create($userBirthday))->y;
		}
	}else{
		return "<div class='error'>You do not have permission to see this, sorry.</div>";
	}

	//Continue only if there is a selected user
	if(!is_numeric($userId)){
		return "<div class='error'>No user to display</div>";
	}

	/*
		Dashboard
	*/
	if(in_array('usermanagement', $userRoles ) || $showCurrentUserData){
		if($showCurrentUserData){
			$admin 		= false;
		}else{
			$admin 		= true;
		}
		
		//Add a tab button
		$tabs[]	= "<li class='tablink active' id='show_dashboard' data-target='dashboard'>Dashboard</li>";
		$html .= "<div id='dashboard'>".showDashboard($userId, $admin).'</div>';
	}

	/*
		Family Info
	*/
	if((array_intersect($genericInfoRoles, $userRoles ) || $showCurrentUserData) && in_array('family', $availableForms) ){
		if($userAge > 18){
			//Tab button
			$tabs[]	= '<li class="tablink" id="show_family_info" data-target="family_info">Family</li>';
			
			//Content
			$familyHtml = '<div id="family_info" class="tabcontent hidden">';

				$familyHtml .= do_shortcode('[formbuilder formname=user_family]');
				
			$familyHtml .= '</div>';
		}

		$html.= $familyHtml;
	}
	
	/*
		GENERIC Info
	*/
	if((array_intersect($genericInfoRoles, $userRoles ) || $showCurrentUserData) && in_array('generic', $availableForms)){
		$accountValidity = get_user_meta( $userId, 'account_validity',true);
		
		//Add a tab button
		$tabs[]	= '<li class="tablink" id="show_generic_info" data-target="generic_info">Generic info</li>';

		$html	.= "<div id='generic_info' class='tabcontent hidden'>";
			if($accountValidity != '' && $accountValidity != 'unlimited' && !is_numeric($accountValidity)){
				$removalDate 	= date_create($accountValidity);
				
				$html	.= "<div id='validity_warning' style='border: 3px solid #bd2919; padding: 10px;'>";
					if(array_intersect($genericInfoRoles, $userRoles )){
						wp_enqueue_script( 'sim_user_management');
						
						$html	.= "<form>";
							$html	.= "<input type='hidden' name='userid' value='$userId'>";
							$html	.= "This user account is only valid till ".date_format($removalDate, "d F Y");
							$html	.= "<br><br>";
							$html	.= "Change expiry date to";
							$html	.= "<input type='date' name='new_expiry_date' min='$accountValidity' style='width:auto; display: initial; padding:0px; margin:0px;'>";
							$html	.= "<br>";
							$html	.= "<input type='checkbox' name='unlimited' value='unlimited' style='width:auto; display: initial; padding:0px; margin:0px;'>";
							$html	.= "<label for='unlimited'> Check if the useraccount should never expire.</label>";
							$html	.= "<br>";
							$html	.= SIM\addSaveButton('extend_validity', 'Change validity');
						$html	.= "</form>";
					}else{
						$html	.= "<p>";
							$html	.= "Your user account will be automatically deactivated on ".date_format($removalDate, "d F Y").".";
						$html	.= "</p>";
					}
				$html	.= "</div>";
			}

			$html	.= do_shortcode('[formbuilder formname=user_generics]');

		$html	.= "</div>";
	}
	
	/*
		Location Info
	*/
	if((array_intersect($genericInfoRoles, $userRoles ) || $showCurrentUserData) && in_array('location', $availableForms)){
		//Add tab button
		$tabs[]	= '<li class="tablink" id="show_location_info" data-target="location_info">Location</li>';
		
		//Content
		$locationHtml = '<div id="location_info" class="tabcontent hidden">';
			$locationHtml .= do_shortcode('[formbuilder formname=user_location]');
		$locationHtml .= '</div>';
		$html	.= $locationHtml;
	}
	
	/*
		LOGIN Info
	*/
	if(in_array('usermanagement', $userRoles )){				
		//Add a tab button
		$tabs[]	= '<li class="tablink" id="show_login_info" data-target="login_info">Login info</li>';
		
		$html .= change_password_form($userId);
	}
				
	/*
		PROFILE PICTURE Info
	*/
	if((in_array('usermanagement',$userRoles ) || $showCurrentUserData) && in_array('profile picture', $availableForms)){
		//Add tab button
		$tabs[]	= '<li class="tablink" id="show_profile_picture_info" data-target="profile_picture_info">Profile picture</li>';
		
		//Content
		$pictureHtml = '<div id="profile_picture_info" class="tabcontent hidden">';
			$pictureHtml .= do_shortcode('[formbuilder formname=profile_picture]');
		$pictureHtml .= '</div>';

		$html	.= $pictureHtml;
	}
	
	/*
		Roles
	*/
	if(in_array('rolemanagement', $userRoles ) || in_array('administrator', $userRoles )){
		//Add a tab button
		$tabs[]	= '<li class="tablink" id="show_roles" data-target="role_info">Roles</li>';
		
		//Content
		$roleHtml = '<div id="role_info" class="tabcontent hidden">'; 
			$roleHtml .= displayRoles($userId);
		$roleHtml .= '</div>';

		$html	.= $roleHtml;
	}
		
	/*
		SECURITY INFO
	*/
	if((array_intersect($genericInfoRoles, $userRoles ) || $showCurrentUserData) && in_array('security', $availableForms)){				
		//Tab button
		$tabs[]	= "<li class='tablink' id='show_security_info' data-target='security_info'>Security</li>";
		
		//Content
		$security = "<div id='security_info' class='tabcontent hidden'>";
			$security .= do_shortcode('[formbuilder formname=security_questions]');
		$security .= '</div>';

		$html	.= $security;
	}

	/*
		Vaccinations Info
	*/
	if((array_intersect($medicalRoles, $userRoles) || $showCurrentUserData) && in_array('vaccinations', $availableForms)){
		if($showCurrentUserData){
			$active = '';
			$class = 'class="hidden"';
		}else{
			$active = 'active';
			$class = '';
		}
		
		//Add tab button
		$tabs[]	= "<li class='tablink $active' id='show_medical_info' data-target='medical_info'>Vaccinations</li>";
		
		//Content
		$html	.= "<div id='medical_info' $class>";
			$html	.= do_shortcode('[formbuilder formname=user_medical]');
			$html	.= "<form method='post' id='print_medicals-form'>";
				$html	.= "<input type='hidden' name='userid' id='userid' value='$userId'>";
				$html	.= "<button class='button button-primary' type='submit' name='print_medicals' value='generate'>Export data as PDF</button>";
			$html	.= "</form>";
		$html	.= "</div>";
	}

	//  Add filter to add extra pages, children tabs should always be last
	$filteredHtml	= apply_filters('sim_user_info_page', ['tabs'=>$tabs, 'html'=>$html], $showCurrentUserData, $user, $userAge);
	$tabs		 	= $filteredHtml['tabs'];
	$html	 		= $filteredHtml['html'];
	
	/*
		CHILDREN TABS
	*/
	if($showCurrentUserData){
		$family = get_user_meta($userId, 'family', true);
		if(is_array($family) && isset($family['children']) && is_array($family['children'])){
			foreach($family['children'] as $childId){
				$firstName = get_userdata($childId)->first_name;
				//Add tab button
				$tabs[]	= "<li class='tablink' id='show_child_info_$childId' data-target='child_info_$childId'>$firstName</li>";
				
				//Content
				$childHtml = "<div id='child_info_$childId' class='tabcontent hidden'>";
					$childHtml .= showChildrenFields($childId);
				$childHtml .= '</div>';
				
				$html	.= $childHtml;
			}
		}
	}

	$result	= "<nav id='profile_menu'>";
		$result	.= "<ul id='profile_menu_list'>";
		foreach($tabs as $tab){
			$result	.= $tab;
		}
		$result	.= "</ul>";
	$result	.= "</nav>";

	$result	.= "<div id='profile_forms'>";
		$result .= "<input type='hidden' class='input-text' name='userid' value='$userId'>";
		$result	.= $html;
	$result	.= "</div>";

	return $result;
}

//Delete user shortcode
add_shortcode( 'delete_user', function(){
	require_once(ABSPATH.'wp-admin/includes/user.php');
	
	$user = wp_get_current_user();

	if ( !in_array('usermanagement', $user->roles)){
		return "<div class='error'>You have no permission to delete user accounts!</div>";
	}

	//Load js	
	wp_enqueue_script('user_select_script');

	$html = "";
	
	if(isset($_GET["userid"])){
		$userId = $_GET["userid"];
		$userdata = get_userdata($userId);
		if(!$userdata){
			return "<div class='error'>User with id $userId does not exist.</div>";
		}

		$family 		= get_user_meta($userId, "family", true);
		$nonceString 	= 'delete_user_'.$userId.'_nonce';
		
		if(!isset($_GET["confirm"])){
			$html	.="<script>";
				$html	.= "var remove = confirm('Are you sure you want to remove the useraccount for $userdata->display_name?');";
				$html	.= "if(remove){";
					$html	.= "var url=`\${window.location}&$nonceString=".wp_create_nonce($nonceString)."`;";
					if (is_array($family) && !empty($family)){
						$html	.= "var family = confirm('Do you want to delete all useraccounts for the familymembers of $userdata->display_name as well?');";
						$html	.= "if(family){";
							$html	.= "window.location = url+'&confirm=true&family=true'";
						$html	.= "}else{";
							$html	.= "window.location = url+'&confirm=true'";
						$html	.= "}";
					}else{
						$html	.= "window.location = url+'&confirm=true'";
					}
				$html	.= "}";
			$html	.= "</script>";
		}elseif($_GET["confirm"] == "true"){
			if(!isset($_GET[$nonceString]) || !wp_create_nonce($_GET[$nonceString],$nonceString)){
				$html .='<div class="error">Invalid nonce! Refresh the page</div>';
			}else{
				$deletedName = $userdata->display_name;
				if(isset($_GET["family"]) && $_GET["family"] == "true" && is_array($family) && !empty($family)){
					$deletedName .= " and all the family";
					if (isset($family["children"])){
						$family = array_merge($family["children"],$family);
						unset($family["children"]);
					}
					foreach($family as $relative){
						//Remove user account
						wp_delete_user($relative,1);
					}
				}
				//Remove user account
				wp_delete_user($userId,1);
				$html .= "<div class='success'>Useraccount for $deletedName succcesfully deleted.</div>";
				$html .= "<script>";
					$html .= "setTimeout(function(){";
						$html .= "window.location = window.location.href.replace('/?userid=$userId&delete_user_{$userId}_nonce=".$_GET[$nonceString]."&confirm=true','').replace('&family=true','');";
					$html .= "}, 3000);";
				$html .= "</script>";
			}
		}
	}
	
	$html .= SIM\userSelect("Select an user to delete from the website:");
	
	return $html;
});

add_shortcode("userstatistics",function (){
	wp_enqueue_script('sim_table_script');

	ob_start();

	$users 		= SIM\getUserAccounts(false, true);

	$baseUrl	= SIM\ADMIN\getDefaultPageLink(MODULE_SLUG, 'user_edit_page');
	?>
	<br>
	<div class='table-wrapper'>
		<table class='sim-table' style='max-height:500px;'>
			<thead>
				<tr>
					<th>Name</th>
					<th>Login count</th>
					<th>Last login</th>
					<th>Mandatory pages to read</th>
					<th>User roles</th>
					<th>Account validity</th>
				</tr>
			</thead>

			<tbody>
				<?php
				foreach($users as $user){
					$loginCount= get_user_meta($user->ID,'login_count',true);
					if(!is_numeric(($loginCount))){
						$loginCount = 0;
					}

					$lastLoginDate	= get_user_meta($user->ID,'last_login_date',true);
					if(empty($lastLoginDate)){
						$lastLoginDate	= 'Never';
					}else{
						$timeString 	= strtotime($lastLoginDate);
						if($timeString ){
							$lastLoginDate = date('d F Y', $timeString);
						}
					}

					$picture = SIM\displayProfilePicture($user->ID);

					echo "<tr class='table-row'>";
						echo "<td>$picture <a href='$baseUrl/?userid=$user->ID'>{$user->display_name}</a></td>";
						echo "<td>$loginCount</td>";
						echo "<td>$lastLoginDate</td>";
						if(function_exists('SIM\MANDATORY\mustReadDocuments')){
							echo "<td>".SIM\MANDATORY\mustReadDocuments($user->ID,true)."</td>";
						}
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