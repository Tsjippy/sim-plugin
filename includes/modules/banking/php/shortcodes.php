<?php
namespace SIM\BANKING;
use SIM;

//Shortcode for financial items
add_shortcode("account_statements", __NAMESPACE__.'\showStatements');

/**
 * show an users bank statements
 * 
 * @return string html containing the staement overview 
 */
function showStatements($userId=''){
	if(isset($_GET["id"]) && is_numeric($_GET["id"])){
		$userId = $_GET["id"];
	}

	$accountStatements = get_user_meta($userId, "account_statements", true);
	
	if(SIM\isChild($userId) || !is_array($accountStatements)){
		return '';
	}

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
				foreach($accountStatements as $year=>$monthArray){
					$visibility	= printYears($year);

					if(!is_array($monthArray)){
						continue;
					}

					printRows($monthArray, $year, $visibility);
				}
				?>
			</tbody>
		</table>
	</div>

	<?php
	return ob_get_clean();
}

function printYears($year){
	if(date("Y") == $year){
		$buttonText 	= "Hide $year";
		$visibility 	= '';
	}else{
		$buttonText 	= "Show $year";
		$visibility 	= ' style="display:none;"';
	}
		
	echo "<button type='button' class='statement_button button' data-target='_$year' style='margin-right: 10px; padding: 0px 10px;'>$buttonText</button>";
	
	return $visibility;
}

function printRows($monthArray, $year, $visibility){
	$monthCount = count($monthArray);
	$firstMonth = array_key_first($monthArray);
	foreach($monthArray as $month => $url){
		$siteUrl	= site_url();
		if(strpos($url, $siteUrl) === false){
			$url = $siteUrl.$url;
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