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
	if(!is_numeric($userId)){
		if(isset($_GET["id"]) && is_numeric($_GET["id"])){
			$userId = $_GET["id"];
		}else{
			$userId	= get_current_user_id();
		}
	}

	$accountStatements = get_user_meta($userId, "account_statements", true);
	
	if(SIM\isChild($userId) || !is_array($accountStatements)){
		if(defined('REST_REQUEST')){
			return 'No statements found';
		}
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

/**
 * Prints the year buttons
 *
 * @param	int	$year	The year to output
 *
 * @param	bool		Whether the year is visible or not
 */
function printYears($year){
	if(date("Y") == $year){
		$buttonText 	= "Hide $year";
		$visibility 	= '';
	}else{
		$buttonText 	= "Show $year";
		$visibility 	= ' style="display:none;"';
	}
		
	echo "<button type='button' class='statement_button button' data-target='_$year'>$buttonText</button>";
	
	return $visibility;
}

/**
 * Prints all the account statement links for a given year
 *
 * @param	array	$monthArray	The months
 * @param	int		$year		The year
 * @param	bool	$visibility	Whether the links should be shown
 */
function printRows($monthArray, $year, $visibility){
	$monthCount = count($monthArray);
	$firstMonth = array_key_first($monthArray);

	foreach($monthArray as $month => $url){
		$url	= SIM\pathToUrl(STATEMENT_FOLDER.$url);
		
		echo "<tr class='_$year'$visibility>";
			if($firstMonth == $month){
				echo "<td rowspan='$monthCount'>";
					echo "<strong>$year<strong>";
				echo "</td>";
			}
			?>
			<td>
				<?php
				echo " <a href='$url'>$month</a>" ;
				?>
			</td>
			<td>
				<a class='statement' href='<?php echo $url;?>'>Download</a>
			</td>
		</tr>
		<?php
	}
}