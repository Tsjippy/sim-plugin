<?php
namespace SIM\BANKING;
use SIM;

//Shortcode for financial items
add_shortcode("account_statements", __NAMESPACE__.'\show_statements');

function show_statements(){
	global $current_user;
	
	if(isset($_GET["id"])){
		$user_id = $_GET["id"];
	}else{
		$user_id = $current_user->ID;
	}

	$account_statements = get_user_meta($user_id, "account_statements", true);
	
	if(SIM\is_child($user_id) == false and is_array($account_statements)){
		//Load js
		wp_enqueue_style('sim_account_statements_style');
		
		wp_enqueue_script('sim_account_statements_script');

		ksort($account_statements);

		ob_start();
		
		?>
		<div class='account_statements'>
			<h3>Account statements</h3>
			<table id="account_statements">
				<tbody>
					<?php
					foreach($account_statements as $year=>$month_array){
						if(date("Y") == $year){
							$button_text 	= "Hide $year";
							$visibility 	= '';
						}else{
							$button_text 	= "Show $year";
							$visibility 	= ' style="display:none;"';
						}
							
						echo "<button type='button' class='statement_button button' data-target='_$year' style='margin-right: 10px; padding: 0px 10px;'>$button_text</button>";
						if(is_array($month_array)){
							$month_count = count($month_array);
							$first_month = array_key_first($month_array);
							foreach($month_array as $month => $url){
								$site_url	= site_url();
								if(strpos($url, $site_url) === false){
									$url = $site_url.$url;
								}
								
								echo "<tr class='_$year'$visibility>";
									if($first_month == $month){
										echo "<td rowspan='$month_count'>";
											echo "<strong>$year<strong>";
										echo "</td>";
									}
									?>
									<td>
										<?php
										echo " <a href='$url>'>$month</a>" ;
										?>
									</td>
									<td>
										<a class='statement' href='<?php echo $url;?>'>Download</a>
									</td>
								</tr>
								<?php
							}
						}
					}
					?>
				</tbody>
			</table>
		</div>

		<?php
		return ob_get_clean();
	}
}