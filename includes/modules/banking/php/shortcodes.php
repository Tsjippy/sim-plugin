<?php
namespace SIM\BANKING;
use SIM;

//Shortcode for financial items
add_shortcode("account_statements", __NAMESPACE__.'\show_statements');

function show_statements(){
	global $current_user;
	
	if(isset($_GET["id"])){
		$userId = $_GET["id"];
	}else{
		$userId = $current_user->ID;
	}

	$accountStatements = get_user_meta($userId, "account_statements", true);
	
	if(SIM\isChild($userId) == false and is_array($accountStatements)){
		//Load js
		wp_enqueue_style('sim_account_statements_style');
		
		wp_enqueue_script('sim_account_statements_script');

		ksort($accountStatements);

		ob_start();
		
		?>
		<div class='account_statements'>
			<h3>Account statements</h3>
			<table id="account_statements">
				<tbody>
					<?php
					foreach($accountStatements as $year=>$month_array){
						if(date("Y") == $year){
							$buttonText 	= "Hide $year";
							$visibility 	= '';
						}else{
							$buttonText 	= "Show $year";
							$visibility 	= ' style="display:none;"';
						}
							
						echo "<button type='button' class='statement_button button' data-target='_$year' style='margin-right: 10px; padding: 0px 10px;'>$buttonText</button>";
						if(is_array($month_array)){
							$monthCount = count($month_array);
							$firstMonth = array_key_first($month_array);
							foreach($month_array as $month => $url){
								$site_url	= site_url();
								if(strpos($url, $site_url) === false){
									$url = $site_url.$url;
								}
								
								echo "<tr class='_$year'$visibility>";
									if($firstMonth == $month){
										echo "<td rowspan='$monthCount'>";
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