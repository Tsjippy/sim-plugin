<?php
namespace SIM\USERMANAGEMENT;
use SIM;

function show_dashboard($user_id, $admin=false){
	if(!is_numeric($user_id)) return "<p>Invalid user id $user_id</p>";

	global $MinistrieIconID;

	ob_start();
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
		echo "<p id='login_message' style='border: 3px solid #bd2919; padding: 10px; text-align: center;'>$message</p>";
	}
	
	echo "<p>Hello $first_name</p>";
	
	?>
	<div id="warnings">
		<?php
		do_action('sim_dashboard_warnings', $user_id, $admin);
		?>
	</div>

	<?php
	do_action('sim_user_dashboard', $user_id, $admin);
	?>
	
	<div id="ministrywarnings">
		<?php
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
			
			//Get the first warning parameter and convert to days
			$days = SIM\get_module_option('frontend_posting', 'max_page_age')*30;
			
			//If the page is not modified since the parameter
			if ($page_age > $days ){
				//Get the edit page url
				$url	= SIM\getValidPageLink(SIM\get_module_option('frontend_posting', 'publish_post_page') );
				if(!$url) $url = '';
				$url = add_query_arg( ['post_id' => $post_id], $url );

				$post_age_warning_html .= '<li><a href="'.$url.'">'.$post_title.'</a></li>';
			}
		}
		if ($post_age_warning_html != $html_start){
			$post_age_warning_html .= "</ul></p>";
			echo $post_age_warning_html;
		}
	echo '</div>';

	return ob_get_clean();
}