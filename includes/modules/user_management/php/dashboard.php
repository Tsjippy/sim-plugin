<?php
namespace SIM\USERMANAGEMENT;
use SIM;

function show_dashboard($userId, $admin=false){
	if(!is_numeric($userId)) return "<p>Invalid user id $userId</p>";

	global $MinistrieIconID;

	ob_start();
	$userdata	= get_userdata($userId);
	$firstName	= $userdata->first_name;
	
	if($admin){
		$loginCount = get_user_meta( $userId, 'login_count', true);
		$lastLogin	= get_user_meta( $userId, 'last_login_date',true);

		if(is_numeric($loginCount)){
			$timeString 	= strtotime($lastLogin);
			if($timeString ) $last_login = date('d F Y', $timeString);
			$message = "$firstName has logged in $loginCount times.<br>Last login was $last_login.";
		}else{
			$message = "$firstName has never logged in.<br>";
		}
		
		//show last login date
		echo "<p id='login_message' style='border: 3px solid #bd2919; padding: 10px; text-align: center;'>$message</p>";
	}
	
	echo "<p>Hello $firstName</p>";
	
	?>
	<div id="warnings">
		<?php
		do_action('sim_dashboard_warnings', $userId, $admin);
		?>
	</div>

	<?php
	do_action('sim_user_dashboard', $userId, $admin);
	?>
	
	<div id="ministrywarnings">
		<?php
		//Show warning about out of date ministry pages
		$ministryPages = get_pages([
			'meta_key'         => 'icon_id',
			'meta_value'       => $MinistrieIconID
		]);
			
		$warningHtml	= '';
		//Loop over all the pages
		foreach ( $ministryPages as $ministryPage ) {
			//Get the ID of the current page
			$postId		= $ministryPage->ID;
			$postTitle	= $ministryPage->post_title;
			
			//Get the last modified date
			$date1		= date_create($ministryPage->post_modified);
			$today		= date_create('now');
			
			//days since last modified
			$pageAge	= date_diff($date1,$today);
			$pageAge 	= $pageAge->format("%a");
			
			//Get the first warning parameter and convert to days
			$days 		= SIM\getModuleOption('frontend_posting', 'max_page_age') * 30;
			
			//If the page is not modified since the parameter
			if ($pageAge > $days ){
				//Get the edit page url
				$url			= SIM\getValidPageLink(SIM\getModuleOption('frontend_posting', 'publish_post_page') );
				if(!$url) $url 	= '';
				$url			= add_query_arg( ['post_id' => $postId], $url );

				$warningHtml 	.= "<li><a href='$url'>$postTitle</a></li>";
			}
		}
		if (!empty($warningHtml)){
			echo "<h3>Notice</h3>";
			echo "<p>";
				echo "Please update these pages:<br>";
				echo "<ul>";
					echo $warningHtml;
				echo "</ul>";
			echo"</p>";
		}
	echo '</div>';

	return ob_get_clean();
}