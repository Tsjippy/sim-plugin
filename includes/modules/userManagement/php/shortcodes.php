<?php
namespace SIM\USERMANAGEMENT;
use SIM;

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
}, 20);

//Shortcode for expiry warnings
add_shortcode("expiry_warnings", __NAMESPACE__.'\expiryWarnings');
function expiryWarnings(){
	if(!empty($_GET["userid"]) && is_numeric($_GET["userid"]) && in_array('usermanagement', wp_get_current_user()->roles )){
		$userId	= $_GET["userid"];
	}else{
		$userId = get_current_user_id();
	}

	$dashboardWarnings	= new DashboardWarnings($userId);

	$dashboardWarnings->greenCardReminder();
		
	$dashboardWarnings->vaccinationReminders();
	
	$dashboardWarnings->reviewReminder();

	if (empty($dashboardWarnings->reminderHtml)){
		if(str_contains($_SERVER['REQUEST_URI'], 'wp-admin/post.php') || str_contains($_SERVER['REQUEST_URI'], 'wp-json')){
			return 'Reminder block<br>This will show empty as you have no reminders';
		}

		return '';
	}

	$html = '<h3 class="frontpage">';
	if($dashboardWarnings->reminderCount > 1){
		$html 			.= 'Reminders</h3><p>'.$dashboardWarnings->reminderHtml;
	}else{
		$dashboardWarnings->reminderHtml 	= str_replace(['</li>', '<li>'], '', $dashboardWarnings->reminderHtml);
		$html 			.= 'Reminder</h3><p>'.$dashboardWarnings->reminderHtml;
	}
	
	return  "<div id=reminders>$html</p></div>";
}

add_shortcode("userstatistics",function (){

	add_filter('post-edit-button', function($buttonHtml, $post, $content){
		return $buttonHtml."<form style='display: inline-block;' action='' method='post'><button class='button small' name='getlist' value='true'>Get Contact List</button></form>";
	}, 10, 3);

	if(isset($_REQUEST['getlist'])){
		SIM\USERPAGE\buildUserDetailPdf('screen');
		return;
	}

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