<?php
namespace SIM;

function show_dashboard($user_id, $admin=false){
	if(!is_numeric($user_id)) return "<p>Invalid user id $user_id";
	global $wpdb;
	global $MinistrieIconID;
	global $PostOutOfDataWarning;
	global $PublishPostPage;
	
	$html 		= '';
	$userdata	= get_userdata($user_id);
	$first_name	= $userdata->first_name;
	
	if($admin){
		$login_count = get_user_meta( $user_id, 'login_count', true);
		$last_login = get_user_meta( $user_id, 'last_login_date',true);

		if(is_numeric($login_count)){
			$time_string 	= strtotime($last_login);
			if($time_string ) $last_login = date('d F Y', $time_string);
			$message = "$first_name has logged in $login_count times.<br>Last login was $last_login.";
		}else{
			$message = "$first_name has never logged in.<br>";
		}
		
		//show last login date
		$html .= "<p id='login_message' style='border: 3px solid #bd2919; padding: 10px; text-align: center;'>$message</p>";
	}
	
	$html .= "<p>Hello $first_name</p>";
	
	$html .= get_must_read_documents($user_id);
	
	$html .= '<div id="warnings">';
	
	$required_html	 = get_required_fields($user_id);
		
	$warning_html	 = get_recommended_fields($user_id);
	
	if (empty($required_html) and empty($warning_html)){
		$html .= "<p>All your data is up to date, well done.</p>";
	}else{
		$html .= "<h3>Please finish your account:</h3>";
	}
		
	if ($required_html != ""){
		$html .= "<p>Please fill in these required fields:</p><ul>".$required_html;
	}
	if ($warning_html != ""){
		$html .= "<p>Please fill in these recommended fields:<br></p><ul>".$warning_html;
	}

	//If there are reminders, show them
	$html .= do_shortcode('[expiry_warnings]');
		
	$html .= '</div><div id="Account statements" style="margin-top:20px;">';
	$html .= do_shortcode('[account_statements]');
	$html .= '</div><div id="ministrywarnings">';

	//Show warning about out of date ministry pages
	$ministry_pages = get_pages([
		'meta_key'         => 'icon_id',
		'meta_value'       => $MinistrieIconID
	]);
		
	$html_start = "<h3>Notice</h3><p>Please update these pages:<br><ul>";
	$post_age_warning_html = $html_start;
	
	//Loop over all the pages
	foreach ( $ministry_pages as $ministry_page ) {
		//Get the ID of the current page
		$post_id = $ministry_page->ID;
		$post_title = $ministry_page->post_title;
		
		//Get the last modified date
		$date1=date_create($ministry_page->post_modified);
		$today=date_create('now');
		
		//days since last modified
		$page_age=date_diff($date1,$today);
		$page_age = $page_age->format("%a");
		
		//Get the firs warning parameter and convert to days
		$days = $PostOutOfDataWarning[0]*30;
		
		//If the page is not modified since the parameter
		if ($page_age > $days ){
			//Get the edit page url
			$url = add_query_arg( ['post_id' => $post_id], get_permalink( $PublishPostPage ) );

			$post_age_warning_html .= '<li><a href="'.$url.'">'.$post_title.'</a></li>';
		}
	}
	if ($post_age_warning_html != $html_start){
		$post_age_warning_html .= "</ul></p>";
		$html .= $post_age_warning_html;
	}
	$html .= '</div>';
	$html .= do_shortcode("[schedules]");

	return $html;
}