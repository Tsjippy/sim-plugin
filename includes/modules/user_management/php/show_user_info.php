<?php
namespace SIM\USERMANAGEMENT;
use SIM;

/* HELPER FUNCTIONS */
//add special js to the dynamic form js
add_filter('form_extra_js',function($formname){
	return file_get_contents(__DIR__ ."/../js/".$formname.".js");
});

//Shortcode for userdata forms
add_shortcode("user-info",'SIM\USERMANAGEMENT\user_info_page');
function user_info_page($atts){
	if(is_user_logged_in()){

		wp_enqueue_style('sim_forms_style');
		
		$a = shortcode_atts( array(
			'currentuser' => false,
		), $atts );
		$show_current_user_data = $a['currentuser'];
		
		//Variables
		$medical_roles		= ["medicalinfo"];
		$generic_info_roles = array_merge(['usermanagement'],$medical_roles,['administrator']);
		$visa_roles 		= ["visainfo"];
		$user 				= wp_get_current_user();
		$user_roles 		= $user->roles;
		$tab_html 			= "<nav id='profile_menu'><ul id='profile_menu_list'>";
		$select_user_html	= '';
		$html				= '';
		$user_age 			= 19;
	
		//Showing data for current user
		if($show_current_user_data){
			$user_id = get_current_user_id();
		//Display a select to choose which users data should be shown
		}else{
			//Show the select user to allowed user only
			if(array_intersect(array_merge($generic_info_roles,$visa_roles), $user_roles )){
				$a = shortcode_atts( 
					array('id' => '', ), 
					$atts 
				);
				$user_id = $a['id'];
				
				if(isset($_GET["userid"]) and get_userdata($_GET["userid"])){
					$user_id = $_GET["userid"];
				}else{
					echo SIM\user_select("Select an user to show the data of:");
				}

				$user_birthday = get_user_meta($user_id, "birthday", true);
				if($user_birthday != "")	$user_age = date_diff(date_create(date("Y-m-d")),date_create($user_birthday))->y;
				
			}else{
				return "<p>You do not have permission to see this, sorry.</p>";
			}
		}

		$local_nigerian		= get_user_meta( $user_id, 'local_nigerian', true );
	
		//Continue only if there is a selected user
		if(is_numeric($user_id)){
			$html .= "<div id='profile_forms'>";
				$html .= '<input type="hidden" class="input-text" name="userid" id="userid" value="'.$user_id.'">';
			
			/*
				Dashboard
			*/
			if(in_array('usermanagement', $user_roles ) or $show_current_user_data){
				if($show_current_user_data){
					$admin 		= false;
				}else{
					$admin 		= true;
				}
				
				//Add a tab button
				$tab_html .= "<li class='tablink active' id='show_dashboard' data-target='dashboard'>Dashboard</li> ";
				$html .= "<div id='dashboard'>".show_dashboard($user_id,$admin).'</div>';
			}
			
			/*
				LOGIN Info
			*/
			if(in_array('usermanagement', $user_roles )){				
				//Add a tab button
				$tab_html .= '<li class="tablink" id="show_login_info" data-target="login_info">Login info</li> ';
				
				$html .= change_password_field($user_id);
			}
			
			/*
				GENERIC Info
			*/
			if(array_intersect($generic_info_roles, $user_roles ) or $show_current_user_data){
				$account_validity = get_user_meta( $user_id, 'account_validity',true);
				
				//Add a tab button
				$tab_html .= '<li class="tablink" id="show_generic_info" data-target="generic_info">Generic info</li>';
				
				//Content
				$html .= '<div id="generic_info" class="tabcontent hidden">';
				if($account_validity != '' and $account_validity != 'unlimited' and !is_numeric($account_validity)){
					$removal_date 	= date_create($account_validity);
					$nonce 			= wp_create_nonce("extend_validity_reset_nonce");
					
					$html .= "<div id='validity_warning' style='border: 3px solid #bd2919; padding: 10px;'>";

					if(array_intersect($generic_info_roles, $user_roles )){
						$html .= "<p>";
							$html .= "This user account is only valid till ".date_format($removal_date,"d F Y").".<br>";
							$html .= "<br>";
							$html .= "<input type='hidden' id='extend_validity_reset_nonce' value='$nonce'>";
							$html .= "Change expiry date to ";
							$html .= "<input type='date' id='new_expiry_date' min='$account_validity' style='width:auto; display: initial; padding:0px; margin:0px;'>";
							$html .= "<br>";
							$html .= "<input type='checkbox' id='unlimited' value='unlimited' style='width:auto; display: initial; padding:0px; margin:0px;'>";
							$html .= "<label for='unlimited'> Check if the useraccount should never expire.</label>";
							$html .= "<br>";
						$html .= "</p>";
						$html .= SIM\add_save_button('extend_validity', 'Change validity');
					}else{
						$html .= "<p>";
							$html .= "Your user account will be automatically deactivated on ".date_format($removal_date,"d F Y").".<br>";
						$html .= "</p>";
					}
					$html .= "</div>";
				}
					$html .= do_shortcode('[formbuilder datatype=user_generics]');
				$html .= '</div>';
			}
			
			/*
				Location Info
			*/
			if(array_intersect($generic_info_roles, $user_roles ) or $show_current_user_data){
				//Add tab button
				$tab_html .= '<li class="tablink" id="show_location_info" data-target="location_info">Location</li> ';
				
				//Content
				$html .= '<div id="location_info" class="tabcontent hidden">';
				$html .= do_shortcode('[formbuilder datatype=user_location]');
				$html .= '</div>';
			}
			
			/*
				Family Info
			*/
			if(array_intersect($generic_info_roles, $user_roles ) or $show_current_user_data){
				if($user_age > 18){
					//Tab button
					$tab_html .= '<li class="tablink" id="show_family_info" data-target="family_info">Family</li> ';
					
					//Content
					$html .= '<div id="family_info" class="tabcontent hidden">';

						$html .= do_shortcode('[formbuilder datatype=user_family]');
						
					$html .= '</div>';
				}elseif(!$show_current_user_data){
					$html .= "<p><br>This user has no family page. ($user_age yr)";
				}
			}
			
			/*
				Roles
			*/
			if(in_array('rolemanagement', $user_roles ) or in_array('administrator', $user_roles )){
				//Add a tab button
				$tab_html .= '<li class="tablink" id="show_roles" data-target="role_info">Roles</li> ';
				
				//Content
				$html .= '<div id="role_info" class="tabcontent hidden">'; 
				$html .= display_roles($user_id);
				$html .= '</div>';
			}
				
			/*
				Visa Info
			*/
			if((array_intersect($visa_roles, $user_roles ) or $show_current_user_data) and empty($local_nigerian)){
				if($user_age > 18){
					if( isset($_POST['print_visa_info'])){
						if(isset($_POST['userid']) and is_numeric($_POST['userid'])){
							export_visa_info_pdf($_POST['userid']);
						}else{
							export_visa_info_pdf($_POST['userid'], true);//export for all people
						}
					}
					
					if( isset($_POST['export_visa_info'])){
						SIM\SIMNIGERIA\export_visa_excel();
					}
				
					//only active if not own data and has not the user management role
					if(!array_intersect(["usermanagement"], $user_roles ) and !$show_current_user_data){
						$active = "active";
						$class = '';
						$tabclass = 'hidden';
					}else{
						$active = "";
						$class = 'hidden';
						$tabclass = '';
					}
					
					//Tab button
					$tab_html .= "<li class='tablink $active $tabclass' id='show_visa_info' data-target='visa_info'>Immigration</li>";
					
					//Content
					$html .= "<div id='visa_info' class='tabcontent $class'>";
					$html .= SIM\SIMNIGERIA\visa_page($user_id,true);
					
					if(array_intersect($visa_roles, $user_roles )){
						$html .= "<div class='export_button_wrapper' style='margin-top:50px;'>
							<form  method='post'>
								<input type='hidden' name='userid' id='userid' value='$user_id'>
								<button class='button button-primary' type='submit' name='print_visa_info' value='generate'>Export user data as PDF</button>
							</form>
							<form method='post'>
								<button class='button button-primary' type='submit' name='print_visa_info' value='generate'>Export ALL data as PDF</button>
							</form>
							<form method='post'>
								<button class='button button-primary' type='submit' name='export_visa_info' value='generate'>Export ALL data to excel</button>
							</form>
						</div>";
					}
					$html .= '</div></div>';
				}elseif(!$show_current_user_data){
					$html .= "<p><br>This user has no visa requirements! ($user_age yr)";
				}
			}

			/*
				SECURITY INFO
			*/
			if((array_intersect($generic_info_roles, $user_roles ) or $show_current_user_data)){				
				//Tab button
				$tab_html .= "<li class='tablink' id='show_security_info' data-target='security_info'>Security</li>";
				
				//Content
				$html .= "<div id='security_info' class='tabcontent hidden'>";
					$html .= do_shortcode('[formbuilder datatype=security_questions]');
				$html .= '</div>';
			}
	
			/*
				Medical Info
			*/
			if((array_intersect($medical_roles, $user_roles) or $show_current_user_data) and empty($local_nigerian)){
				if($show_current_user_data){
					$active = '';
					$class = 'class="hidden"';
				}else{
					$active = 'active';
					$class = '';
				}
				
				//Add tab button
				$tab_html .= "<li class='tablink $active' id='show_medical_info' data-target='medical_info'>Vaccinations</li> ";
				
				//Content
				$html .= "<div id='medical_info' $class><div>";
					$html .= do_shortcode('[formbuilder datatype=user_medical]');
					$html .= '<div>
						<form method="post" id="print_medicals-form">
							<input type="hidden" name="userid" id="userid" value="'.$user_id.'">
							<button class="button button-primary" type="submit" name="print_medicals" value="generate">Export data as PDF</button>
						</form>
					</div>
				</div></div>';
			}
			
			/*
				Two FA Info
			*/
			if($show_current_user_data){
				//Add tab button
				$tab_html .= '<li class="tablink" id="show_2fa_info" data-target="twofa_info">Two factor</li>';
				
				//Content
				$html .= '<div id="twofa_info" class="tabcontent hidden">';
				$html .= SIM\LOGIN\twofa_settings_form($user_id);
				$html .= '</div>';
			}			
			
			/*
				PROFILE PICTURE Info
			*/
			if(in_array('usermanagement',$user_roles ) or $show_current_user_data){
				//Add tab button
				$tab_html .= '<li class="tablink" id="show_profile_picture_info" data-target="profile_picture_info">Profile picture</li>';
				
				//Content
				$html .= '<div id="profile_picture_info" class="tabcontent hidden">';
					$html .= do_shortcode('[formbuilder datatype=profile_picture]');
				$html .= '</div>';
			}
			
			/*
				CHILDREN TABS
			*/
			if($show_current_user_data){
				$family = get_user_meta($user_id,'family',true);
				if(is_array($family) and isset($family['children']) and is_array($family['children'])){
					foreach($family['children'] as $child_id){
						$first_name = get_userdata($child_id)->first_name;
						//Add tab button
						$tab_html .= "<li class='tablink' id='show_child_info_$child_id' data-target='child_info_$child_id'>$first_name</li>";
						
						//Content
						$html .= "<div id='child_info_$child_id' class='tabcontent hidden'>";
							$html .= show_children_fields($child_id);
						$html .= '</div>';
					}
				}
			}
		}
		
		return $select_user_html.$tab_html.'</ul></nav>'.$html.'</div>';
	}else{
		echo SIM\LOGIN\login_modal("You do not have permission to see this, sorry.");
	}
}

function export_visa_info_pdf($user_id=0, $all=false) {
	if($all == true){
		//Build the frontpage
		$pdf = new SIM\PDF\PDF_HTML();
		$pdf->frontpage("Visa user info","");
		
		//Get all adult missionaries
		$users = SIM\get_user_accounts();
		foreach($users as $user){
			$pdf->setHeaderTitle('Greencard information for '.$user->display_name);
			$pdf->PageTitle($pdf->headertitle);
			write_visa_pages($user->ID, $pdf);
		}
	}else{
		//Build the frontpage
		$pdf = new SIM\PDF\PDF_HTML();
		$pdf->frontpage("Visa user info for:",get_userdata($user_id)->display_name);
		write_visa_pages($user_id, $pdf);
	}
	
	$pdf->printpdf();
}

function write_visa_pages($user_id, $pdf){
	$visa_info = get_user_meta( $user_id, "visa_info",true);
	if(!is_array($visa_info)){
		$pdf->Write(10,"No greencard information found.");
		return;
	}
	
	// Post Content	
	$pdf->SetFont( 'Arial', '', 12 );
	
	$qualificationsarray = $visa_info['qualifications'];
	//Visa info without qualifications
	unset($visa_info['qualifications']);
	
	//Understudy info
	$understudiesarray = $visa_info['understudies'];
	//Visa info without understudies
	unset($visa_info['understudies']);
	
	$understudies_documents = [];
	foreach($understudiesarray as $key=>$understudy){
		$understudies_documents[$key] = $understudy['documents'];
		unset($understudiesarray[$key]['documents']);
	}
	
	if(count($visa_info)>0){
		$pdf->WriteArray($visa_info);
	}else{
		$pdf->Write(10,'No greencard details found');
		$pdf->Ln(10);
	}

	if(is_array($understudiesarray) and count($understudiesarray)>0){
		$pdf->PageTitle('Understudy information');
		$pdf->WriteArray($understudiesarray);
	}else{
		$pdf->Write(10,'No understudy information found');
		$pdf->Ln(10);
	}
	
	if(is_array($qualificationsarray) and count($qualificationsarray)>0){
		$pdf->PageTitle('Qualifications');
		$pdf->WriteImageArray($qualificationsarray);
	}else{
		$pdf->Write(10,'No qualifications found');
		$pdf->Ln(10);
	}
	
	if(is_array($understudies_documents) and count($understudies_documents)>0){
		$pdf->AddPage();
		
		foreach($understudies_documents as $key=>$understudies_document){
			if(count($understudies_document)>0){
				if($key>1) $pdf->Ln(10);
				$pdf->PageTitle('Understudy documents for understudy '.$key,false);
				$pdf->WriteImageArray($understudies_document);
			}else{
				$pdf->Write(10,'No understudy documents found for understudy '.$key);
				$pdf->Ln(10);
			}
		}
	}else{
		$pdf->Write(10,'No understudy documents found');
		$pdf->Ln(10);
	}
	
	return $pdf;
}

add_action ( 'wp_ajax_extend_validity', function(){
	//print_array($_POST,true);
	if(isset($_POST['new_expiry_date']) and isset($_POST['userid']) and is_numeric($_POST['userid'])){
		SIM\verify_nonce('extend_validity_reset_nonce');
		
		$user_id = $_POST['userid'];
		if(isset($_POST['unlimited']) and $_POST['unlimited'] == 'unlimited'){
			$date = 'unlimited';
			echo "Marked the useraccount for ".get_userdata($user_id)->first_name." to never expire.";
		}else{
			$date = sanitize_text_field($_POST['new_expiry_date']);
			echo "Extended valitidy for ".get_userdata($user_id)->first_name." till $date";
		}
		update_user_meta( $user_id, 'account_validity',$date);
	}
	wp_die();
	
});