<?php
namespace SIM\USERMANAGEMENT;
use SIM;

// edit users dropdown
add_action('sim_user_description', function($user){
    //Add a useraccount edit button if the user has the usermanagement role
	if (in_array('usermanagement', wp_get_current_user()->roles)){
        $url	= SIM\ADMIN\getDefaultPageLink(MODULE_SLUG, 'user_edit_page');
        if(!$url){
			return;
		}

		$url .= '/?userid=';
		
		$html = "<div class='flex edit_useraccounts'><a href='$url$user->ID' class='button sim'>Edit useraccount for ".$user->first_name."</a>";
        $partner    = SIM\hasPartner($user->ID, true);
		if($partner){
			$html .= "<a  href='$url$partner->ID' class='button sim'>Edit useraccount for $partner->first_name</a>";
		}

        $family = (array)get_user_meta( $user->ID, 'family', true );
		if(isset($family['children'])){
			foreach($family['children'] as $child){
				$html .= "<a href='$url$child' class='button sim'>Edit useraccount for ".get_userdata($child)->first_name."</a>";
			}
		}
		$html .= '</div>';

        echo $html;
	}
});

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
	wp_enqueue_style('sim_useraccount');
	
	wp_enqueue_script( 'sim_userpage' );
	
	$a = shortcode_atts( array(
		'currentuser' 	=> false,
		'id' 			=> '',
	), $atts );

	$showCurrentUserData = $a['currentuser'];
	
	//Variables
	$medicalRoles		= ["medicalinfo"];
	$genericInfoRoles 	= array_merge(['usermanagement'], $medicalRoles, ['administrator']);
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

	$accountType	= get_user_meta($userId, 'account-type', true);
	// positional account with usermanagement rights as a normal account so they can change the forms
	if($accountType == 'positional' && in_array('usermanagement', $userRoles )){
		$accountType	= 'normal';
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
		$html .= "<div id='dashboard'>";
			if(!isset($_GET['main_tab']) || $_GET['main_tab'] == 'dashboard' ){
				$html	.= showDashboard($userId, $admin);
			}else{
				$html	.= "<div class='loader-wrapper loading hidden'></div><img class='tabloader' src='".LOADERIMAGEURL."' loading='lazy'>";
			}
		$html	.= '</div>';
	}

	/*
		Family Info
	*/
	if(
		$accountType != 'positional'	&&							// we are not a positional account
		(
			array_intersect($genericInfoRoles, $userRoles ) ||		// we do  have permission to view others data
			$showCurrentUserData									// or its our own data
		) &&
		in_array('family', $availableForms)							// and the family form is enabled
	){
		if($userAge > 18){
			//Tab button
			$tabs[]	= '<li class="tablink" id="show_family_info" data-target="family_info">Family</li>';
			
			//Content
			$html	.= '<div id="family_info" class="tabcontent hidden">';

				if(isset($_GET['main_tab']) && $_GET['main_tab'] == 'family'){
					$html	.= do_shortcode('[formbuilder formname=user_family]');
				}else{
					$html	.= "<div class='loader-wrapper loading hidden'></div><img class='tabloader' src='".LOADERIMAGEURL."' loading='lazy'>";
				}
				
			$html .= '</div>';
		}
	}
	
	/*
		GENERIC Info
	*/
	if((array_intersect($genericInfoRoles, $userRoles ) || $showCurrentUserData) && in_array('generic', $availableForms)){
		
		//Add a tab button
		$tabs[]	= '<li class="tablink" id="show_generic_info" data-target="generic_info">Generic info</li>';

		$html	.= "<div id='generic_info' class='tabcontent hidden'>";

			if(isset($_GET['main_tab']) && $_GET['main_tab'] == 'generic_info'){
				$html	.= getGenericsTab($userId);
			}else{
				$html	.= "<div class='wrapper loading hidden'></div><img class='tabloader' src='".LOADERIMAGEURL."' loading='lazy'>";
			}

		$html	.= "</div>";
	}
	
	/*
		Location Info
	*/
	if(
		$accountType != 'positional'	&&
		(
			array_intersect($genericInfoRoles, $userRoles ) ||
			$showCurrentUserData
		) &&
		in_array('location', $availableForms)
	){
		//Add tab button
		$tabs[]	= '<li class="tablink" id="show_location_info" data-target="location_info">Location</li>';
		
		//Content
		$html .= '<div id="location_info" class="tabcontent hidden">';
		
			if(isset($_GET['main_tab']) && $_GET['main_tab'] == 'location_info'){
				$html	.= do_shortcode('[formbuilder formname=user_location]');
			}else{
				$html	.= "<div class='loader-wrapper loading hidden'></div><img class='tabloader' src='".LOADERIMAGEURL."' loading='lazy'>";
			}

		$html .= '</div>';
	}
	
	/*
		LOGIN Info
	*/
	if(in_array('usermanagement', $userRoles )){
		//Add a tab button
		$tabs[]	= '<li class="tablink" id="show_login_info" data-target="login_info">Login info</li>';
		
		$html .= changePasswordForm($userId);
	}
				
	/*
		PROFILE PICTURE Info
	*/
	if((in_array('usermanagement',$userRoles ) || $showCurrentUserData) && in_array('profile picture', $availableForms)){
		//Add tab button
		$tabs[]	= '<li class="tablink" id="show_profile_picture_info" data-target="profile_picture_info">Profile picture</li>';
		
		//Content
		$html	.= '<div id="profile_picture_info" class="tabcontent hidden">';

			if(isset($_GET['main_tab']) && $_GET['main_tab'] == 'profile_picture'){
				$html	.= do_shortcode('[formbuilder formname=profile_picture]');
			}else{
				$html	.= "<div class='loader-wrapper loading hidden'></div><img class='tabloader' src='".LOADERIMAGEURL."' loading='lazy'>";
			}

		$html .= '</div>';
	}
	
	/*
		Roles
	*/
	if(in_array('rolemanagement', $userRoles ) || in_array('administrator', $userRoles )){
		//Add a tab button
		$tabs[]	= '<li class="tablink" id="show_roles" data-target="role_info">Roles</li>';
		
		//Content
		ob_start();
		?>
		<div id="role_info" class="tabcontent hidden">
			<h3>Select user roles</h3>
			<p>
				Select the roles this user should have.<br>
				If you want to disable a user go to the login info tab.
			</p>
			<form>
				<input type='hidden' name='userid' value='<?php echo $userId;?>'>
				<?php
				echo displayRoles($userId);
				
				echo SIM\addSaveButton('updateroles', 'Update roles');
				?>
			</form>
		</div>

		<?php
		$html	.= ob_get_clean();
	}
		
	/*
		SECURITY INFO
	*/
	if(
		$accountType != 'positional'	&&
		(
			array_intersect($genericInfoRoles, $userRoles ) ||
			$showCurrentUserData
		) &&
		in_array('security', $availableForms)
	){
		//Tab button
		$tabs[]	= "<li class='tablink' id='show_security_info' data-target='security_info'>Security</li>";
		
		//Content
		$html	.= "<div id='security_info' class='tabcontent hidden'>";

			if(isset($_GET['main_tab']) && $_GET['main_tab'] == "security_info"){
				$html	.= do_shortcode('[formbuilder formname=security_questions]');
			}else{
				$html	.= "<div class='loader-wrapper loading hidden'></div><img class='tabloader' src='".LOADERIMAGEURL."' loading='lazy'>";
			}

		$html .= '</div>';
	}

	/*
		Vaccinations Info
	*/
	if(
		$accountType != 'positional'	&&
		(
			array_intersect($medicalRoles, $userRoles) ||
			$showCurrentUserData
		) &&
		in_array('vaccinations', $availableForms)
	){
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

			if(isset($_GET['main_tab']) && $_GET['main_tab'] == 'medical_info'){
				$html	.= getMedicalTab($userId);
			}else{
				$html	.= "<div class='loader-wrapper loading hidden'></div><img class='tabloader' src='".LOADERIMAGEURL."' loading='lazy'>";
			}

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
		if(is_array($family) && @is_array($family['children'])){
			foreach($family['children'] as $childId){
				$firstName = get_userdata($childId)->first_name;
				//Add tab button
				$tabs[]	= "<li class='tablink' id='show_child_info_$childId' data-target='child_info_$childId'>$firstName</li>";
				
				//Content
				$html	.= "<div id='child_info_$childId' class='tabcontent hidden'>";

					$html	.= "<div class='loader-wrapper loading hidden'></div><img class='tabloader' src='".LOADERIMAGEURL."' loading='lazy'>";

				$html .= '</div>';
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

/**
 * Get the contents of the generics tab
 *
 * @param int		$userId		Wp user id
 *
 * @return	string				The html
 */
function getGenericsTab($userId){
	
	$accountValidity 	= get_user_meta( $userId, 'account_validity',true);

	$medicalRoles		= ["medicalinfo"];
	$genericInfoRoles 	= array_merge(['usermanagement'], $medicalRoles, ['administrator']);

	$user 				= wp_get_current_user();
	$userRoles 			= $user->roles;

	$html	= '';
	if(!empty($accountValidity) && $accountValidity != 'unlimited' && !is_numeric($accountValidity)){
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

	$html	.= do_shortcode("[formbuilder formname=user_generics userid='$userId']");

	return $html;
}

function getMedicalTab($userId){
	ob_start();
	
	echo do_shortcode('[formbuilder formname=user_medical]');
				
	?>
	<form method='post' id='print_medicals-form'>
		<input type='hidden' name='userid' id='userid' value='<?php echo $userId;?>'>
		<button class='button button-primary' type='submit' name='print_medicals' value='generate'>Export data as PDF</button>
	</form>
	<?php

	return ob_get_clean();

}