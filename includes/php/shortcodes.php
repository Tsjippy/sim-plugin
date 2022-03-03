<?php
namespace SIM;

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
	if (is_numeric($current_loggin_count)){
		return number_to_words($current_loggin_count);
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
				wp_enqueue_script('sim_message_script');
				
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
			$users = get_user_accounts(false,true,true,['ID']);
			foreach($users as $user){
				$vcard .= build_vcard($user->ID);
			}
			echo $vcard;
		}elseif($_GET['vcard']=="outlook"){
			$zip = new \ZipArchive;
			
			if ($zip->open('SIMContacts.zip', \ZipArchive::CREATE) === TRUE){
				//Get all user accounts
				$users = get_user_accounts(false,true,true,['ID','display_name']);
				
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
		wp_enqueue_script('sim_account_statements_script');
		
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

//Shortcode for vaccination warnings
add_action('sim_dashboard_warnings', function($user_id){
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
	
	echo $html;
});

// Shortcode to display user in a page or post
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
	
	if(!empty($a['style'])){
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
	
	$url = get_user_page_url($user_id);
	
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
	wp_enqueue_script('sim_table_script');
	ob_start();
	$users = get_user_accounts($return_family=false,$adults=true,$local_nigerians=true);
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
						echo "<td>".MANDATORY\get_must_read_documents($user->ID,true)."</td>";
						//echo "<td>".get_required_fields($user->ID)."</td>";
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

//Shortcode for testing
add_shortcode("test",function ($atts){
	global $wpdb;
	//update all posts where this is attached
	/* 	$users = get_users();
    foreach($users as $user){

	} */

	/*$theme_mods		= get_option('theme_mods_generatepress-child');

	foreach($theme_mods as $key=>$mod){
		if(strpos($key, 'custom_simnigeria') !== false){
			$theme_mods[str_replace('custom_simnigeria', 'sim', $key)]	= $mod;
			unset($theme_mods[$key]);
		}elseif(strpos($key, 'simnigeria') !== false){
			$theme_mods[str_replace('simnigeria', 'sim', $key)]	= $mod;
			unset($theme_mods[$key]);
		}
	}

	update_option('theme_mods_generatepress-child', $theme_mods); */

	//theme_mods_generatepress-child

	//DROP TABLE `{$wpdb->prefix}sim_forms`, `{$wpdb->prefix}sim_form_elements`, `{$wpdb->prefix}sim_form_submissions`
 	/* try{
		$wpdb->query("DROP TABLE `{$wpdb->prefix}sim_statistics`,`{$wpdb->prefix}sim_schedules`,`{$wpdb->prefix}sim_events`,`{$wpdb->prefix}sim_forms`, `{$wpdb->prefix}sim_form_elements`, `{$wpdb->prefix}sim_form_submissions`, {$wpdb->prefix}sim_form_shortcodes");
	}catch(Exception $e) {
		print_array($e);
	 }

	 try{
		$wpdb->query("RENAME TABLE {$wpdb->prefix}simnigeria_form_shortcodes TO {$wpdb->prefix}sim_form_shortcodes");
		$wpdb->query("RENAME TABLE {$wpdb->prefix}simnigeria_forms TO {$wpdb->prefix}sim_forms");
		$wpdb->query("ALTER TABLE `{$wpdb->prefix}sim_forms` CHANGE `form_name` `name` TINYTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL;");
		$wpdb->query("ALTER TABLE `{$wpdb->prefix}sim_forms` CHANGE `form_version` `version` TINYTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL;");
		$wpdb->query("RENAME TABLE {$wpdb->prefix}simnigeria_schedules TO {$wpdb->prefix}sim_schedules");
		$wpdb->query("RENAME TABLE {$wpdb->prefix}simnigeria_statistics TO {$wpdb->prefix}sim_statistics");
		$wpdb->query("RENAME TABLE {$wpdb->prefix}simnigeria_events TO {$wpdb->prefix}sim_events");
	 }catch(Exception $e) {
		print_array($e);
	 }

	$formbuilder = new FORMS\Formbuilder();
	$formbuilder->get_forms();
	$formbuilder->create_db_table();

	foreach($formbuilder->forms as $form){
		$form_elements		= maybe_unserialize($form->form_elements);
		$form_conditions	= maybe_unserialize($form->form_conditions);
		$form_settings		= maybe_unserialize($form->settings);
		$form_emails		= maybe_unserialize($form->emails);

		$id_mapper	= [];

		$priority=1;
		foreach($form_elements as $form_element){
			$form_element['form_id']	= $form->id;

			if(!empty($form_element['labeltext'])){
				$form_element['text']	= $form_element['labeltext'];
			}
			unset($form_element['labeltext']);
			if(!empty($form_element['infotext'])){
				$form_element['text']	= $form_element['infotext'];
			}
			unset($form_element['infotext']);

			unset($form_element['labelwrap']);

			$form_element['priority']	= $priority;
			$priority++;

			if(!empty($form_element['buttontext'])) echo $form_element['buttontext'].'<br>';
			unset($form_element['buttontext']);

			if($form_element['hidden'] == 'hidden') $form_element['hidden']=true;
			if($form_element['required'] == 'required') $form_element['required']=true;
			if($form_element['multiple'] == 'multiple') $form_element['multiple']=true;
			if($form_element['wrap'] == 'wrap') $form_element['wrap']=true;

			$old_id	=	$form_element['id'];
			unset($form_element['id']);

			$result = $wpdb->insert(
				$wpdb->prefix . 'sim_form_elements', 
				$form_element
			);

			$id_mapper[$old_id]	= $wpdb->insert_id;
		}

		//now process the conditions
		foreach($form_elements as $form_element){
			$form_element['form_id']	= $form->id;

			$conditions	= $form_conditions[$form_element['id']];
			if(!empty($conditions)){
				foreach($conditions as $index=>&$condition){
					if($index === 'copyto'){
						foreach($condition as $i=>$v){
							if(is_numeric($v)){
								unset($condition[$i]);
								$id=$id_mapper[$i];
								if(is_numeric($id)){
									$condition[$id]	= $id;
								}
							}
						}
					}else{
						if(is_numeric($condition['property_value'])){
							$id=$id_mapper[$condition['property_value']];
							if(is_numeric($id)){
								//replace with new id
								$condition['property_value']	= $id;
							}
						}

						if(!empty($condition['rules'])){
							foreach($condition['rules'] as &$rule){
								if(is_numeric($rule['conditional_field'])){
									$id=$id_mapper[$rule['conditional_field']];
									if(is_numeric($id)){
										//replace with new id
										$rule['conditional_field']	= $id;
									}
								}
								if(is_numeric($rule['conditional_field_2'])){
									$id=$id_mapper[$rule['conditional_field_2']];
									if(is_numeric($id)){
										//replace with new id
										$rule['conditional_field_2']	= $id;
									}
								}
							}
						}
					}
				}
				//change element ids
				$form_element['conditions']	= serialize($conditions);

				$result = $wpdb->update(
					$wpdb->prefix . 'sim_form_elements', 
					['conditions'	=> serialize($conditions)],
					['id'			=> $id_mapper[$form_element['id']]]
				);
			}
		}

		//Move submissions
		$query							= "SELECT * FROM {$wpdb->prefix}simnigeria_form_submissions_{$form->name} WHERE 1";
		$form_submissions					= $wpdb->get_results($query, ARRAY_A);
		foreach($form_submissions as $submission){
			$submission['form_id'] = $form->id;
			unset($submission['id']);

			$form_results = maybe_unserialize($submission['formresults']);

			if(is_numeric($form_results['id'])){
				$form_results['id']	= $wpdb->get_var( "SELECT AUTO_INCREMENT FROM information_schema.TABLES WHERE (TABLE_NAME = '{$wpdb->prefix}sim_form_submissions')");
			}

			$submission['formresults']=serialize($form_results);

			$wpdb->insert(
				$wpdb->prefix . 'sim_form_submissions', 
				$submission
			);
		}

		//remap the table column settings to the new ids
		$query							= "SELECT * FROM {$wpdb->prefix}sim_form_shortcodes WHERE form_id={$form->id}";
		$form_results					= $wpdb->get_results($query, ARRAY_A);
		foreach($form_results as $result){
			$column_settings		= unserialize($result['column_settings']);

			foreach($column_settings as $index=>$column_setting){
				if($index == -1) continue;
				$column_settings[$id_mapper[$index]] = $column_setting;
				unset($column_settings[$index]);
			}

			$table_settings		= unserialize($result['table_settings']);

			foreach($table_settings as $index=>&$table_setting){
				if(is_numeric($table_setting)){
					$table_setting	= $id_mapper[$table_setting];
				}
			}

			$wpdb->update(
				$wpdb->prefix . 'sim_form_shortcodes', 
				[
					'column_settings'=>serialize($column_settings),
					'table_settings'=>serialize($table_settings),
				],
				['form_id'=>$form->id]
			);
		}

		foreach($form_settings as &$form_setting){
			if(is_numeric($form_setting)){
				$form_setting	= $id_mapper[$form_setting];
			}
		}

		foreach($form_emails as &$form_email){
			foreach($form_email as &$setting){
				if(is_numeric($setting)){
					$setting	= $id_mapper[$setting];
				}
				if(is_array($setting)){
					foreach($setting as &$set){
						if(is_numeric($set['fieldid'])){
							$set['fieldid']	= $id_mapper[$set['fieldid']];
						}
					}
				}
			}
		}

		$wpdb->update(
			$wpdb->prefix . 'sim_forms', 
			[
				'settings'=>serialize($form_settings),
				'emails'=>serialize($form_emails),
			],
			['id'=>$form->id]
		);
	} */

	return '';
});