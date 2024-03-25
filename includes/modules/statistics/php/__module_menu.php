<?php
namespace SIM\STATISTICS;
use SIM;

const MODULE_VERSION		= '7.0.5';
//module slug is the same as grandparent folder name
DEFINE(__NAMESPACE__.'\MODULE_SLUG', strtolower(basename(dirname(__DIR__))));

add_filter('sim_submenu_description', function($description, $moduleSlug){
	//module slug should be the same as the constant
	if($moduleSlug != MODULE_SLUG)	{
		return $description;
	}

	ob_start();
	?>
	<p>
		This module stores page visits per user in the db.<br>
	</p>
	<?php

	return ob_get_clean();
}, 10, 2);

add_filter('sim_submenu_options', function($optionsHtml, $moduleSlug, $settings){
	//module slug should be the same as grandparent folder name
	if($moduleSlug != MODULE_SLUG){
		return $optionsHtml;
	}

	ob_start();
	global $wp_roles;
	?>
    <label>Who should see the statistics?</label><br>
	<?php
	foreach($wp_roles->role_names as $key=>$name){
		if(is_array($settings['view_rights']) && in_array($key, $settings['view_rights'])){
			$checked = 'checked';
		}else{
			$checked = '';
		}
		echo "<input type='checkbox' name='view_rights[]' value='$key' $checked> $name<br>";
	}

	return ob_get_clean();
}, 10, 3);

add_filter('sim_module_data', function($dataHtml, $moduleSlug){
	//module slug should be the same as grandparent folder name
	if($moduleSlug != MODULE_SLUG){
		return $dataHtml;
	}

	if(!isset($_POST['exclude-list'])){
		$_POST['exclude-list']	= '';
	}

	global $wpdb;

    $tableName	= $wpdb->prefix . 'sim_statistics';

	// base query
	$query		= "SELECT `timecreated`,`timelastedited`, `url`, SUM(`counter`) as amount, count(`userid`) as count FROM $tableName WHERE `url` NOT LIKE '/?%' AND `url` NOT LIKE '?%' AND `url` NOT LIKE '%/#%'";

	// parse optional queries
	if(isset($_POST['exclude-editors'])){
		// Exclude editors
		$users 		 = implode(',', get_users( array(
			'role'		=> ['editor'],
			'fields'	=> 'ID'
		) ));
		$query		.= " AND `userid` NOT IN ($users)";
	}

	if(!empty($_POST['months'])){
		$minDate	= Date('Y-m-d', strtotime("- {$_POST['months']}months"));
		$query		.= " AND `timelastedited` > '$minDate'";
	}

	if(!empty($_POST['exclude-list'])){
		$query		.= " AND `url` NOT IN ('".str_replace(',', "','", $_POST['exclude-list'])."')";
	}

	$query		.= " GROUP BY `url` ORDER BY `amount` DESC";

	$limit	= 100;
	if(isset($_POST['max']) && is_numeric($_POST['max'])){
		$limit	= $_POST['max'];
	}
	$query		.= " LIMIT $limit";

	// Get the results
    $pageViews  = $wpdb->get_results( $query );

    ob_start();
	// Add a script to add a page to the exclusion
    ?>
	<script>
		function addExclude(el){
			let form = document.getElementById('statistics-overview-settings');
			let excludeList	= form.querySelector('#exclude-list');
			if(excludeList.value != ''){
				excludeList.value	= excludeList.value+','+el.value;
			}else{
				excludeList.value	= el.value;
			}

			form.submit();
		}
	</script>
    <div class='pagestatistics'>
        <h2>Statistics</h2>

		<form method='post' id='statistics-overview-settings'>
			<input type='hidden' name='exclude-list' id='exclude-list' value='<?php echo $_POST['exclude-list'];?>'>
			<label>
				<input type='checkbox' name='exclude-editors' value='true' <?php if(!empty($_POST['exclude-editors'])){ echo ' checked';} ?>>
				Exclude editors
			</label>
			<br>
			<label>
				Show Statistics from the last <input type='number' name='months' value='<?php echo $_POST['months'];?>' style='max-width: 60px;'> months only
			</label>
			<br>
			<label>
				Show top <input type='number' name='max' value='<?php if(!isset($_POST['max'])){echo 100;}else{echo $_POST['max'];}?>' style='max-width: 60px;'> pages only
			</label>
			<br>
			<input type='submit' value='Apply'>
		</form>

        <table class='statistics_table sim-table'>
			<thead>
				<th>URL</th>
				<th>Total views</th>
				<th>Unique views</th>
				<th>Actions</th>
			</thead>
            <tbody>
				<?php
					foreach($pageViews as $page){
						?>
						<tr>
							<td class='url'><?php echo "<a href='$page->url'>".explode('?', $page->url)[0]."</a>";?></td>
							<td class='total-views'><?php echo $page->amount?></td>
							<td class='unique-views'><?php echo $page->count;?></td>
							<td class='actions'><button class='small exclude-url' value='<?php echo $page->url; ?>' onclick='addExclude(this)'>Exclude</button></td>
					</tr>
						<?php
					}
				?>
            </tbody>
        </table>
    </div>
    <?php

	return ob_get_clean();
}, 10, 2);

add_action('sim_module_activated', function($moduleSlug){
	//module slug should be the same as grandparent folder name
	if($moduleSlug != MODULE_SLUG)	{return;}
	
	$statistics = new Statistics();
	$statistics->createDbTable();
});