<?php
namespace SIM\BULKCHANGE;
use SIM;

/**
 * Shows a table displaying all meta keys with a value allowing to edit them
 * @param  	string 	$metaKey		The meta value to update
 * @param  	array	$allowedRoles   The roles with edit right 
 * @param 	string	$returnFamily   Whether we are editing a family meta value or an individual one
 * 
 * @return	string					The Html
*/
function bulkchangeMeta($metaKey, $allowedRoles, $returnFamily){
	//Get the current user
	$user = wp_get_current_user();
	
	$metaKeyBase = $metaKey;
	$metaKeyName = $metaKey;
	if (strpos($metaKey, '#') !== false){
		$metaKeyBase = explode('#',$metaKey)[0];
		$metaKeyName = explode('#',$metaKey)[1];
		
	}
			
	$displayName = ucfirst(str_replace('_',' ',$metaKeyName));
	
	//User is logged in and has the correct role
	if($user->ID != 0 && array_intersect($allowedRoles, $user->roles ) ) {
		//Load js
		wp_enqueue_script('sim_table_script');
		
		ob_start();
		
		?>
		<h2 id='<?php echo $metaKey;?>_table_title' class='table_title'><?php echo $displayName;?></h2>
		<table class='sim-table' data-url='bulkchange\bulk_change_meta'>
			<thead>
				<tr>
					<th class='dsc'>Name</th>
					<th id='<?php echo $metaKey;?>'><?php echo $displayName;?></th>
				</tr>
			</thead>

			<?php

			//Get all users, sort by last name
			foreach(SIM\getUserAccounts($returnFamily) as $user){
				$value 	= get_user_meta( $user->ID, $metaKeyBase, true );
				
				//Check if the value is an array
				if(is_array($value)){
					$value 	= $value[$metaKeyName];
				}
				
				//Now the value should not be an array
				if(is_array($value)){
					return 'Please provide single value not an array';
				}
					
				if($value == ""){
					$value = "Click to update";	
				}		
				
				?>
				<tr class='table-row' data-meta_key='<?php echo $metaKey;?>'>
					<td><?php echo $user->displayName;?></td>
					<td class='edit' data-user_id='<?php echo $user->ID;?>'><?php echo $value;?></td>
				</tr>
				<?php
			}
			?>
		</table>

		<?php
		return ob_get_clean();	
	}

	return '<p>You do not have permission to view this</p>';
}

/**
 * Shows a table displaying all meta keys with a value allowing to edit them
 * @param  	string 	$metaKey		The meta value to update
 * @param  	string	$targetDir   	The folder where the files should be uploaded 
 * @param 	string	$returnFamily   Whether we are editing a family meta value or an individual one
 * 
 * @return	string					the html
*/
function bulkChangeUpload($metaKey, $targetDir){
	if (strpos($metaKey, '#') !== false){
		$metaKeyBase 	= explode('#', $metaKey)[0];
		$metaKeyName 	= explode('#', $metaKey)[1];
		$metaKey		= $metaKeyBase.'['.$metaKeyName.']';				
	}else{
		$metaKeyBase 	= $metaKey;
		$metaKeyName 	= $metaKey;
		$metaKey		= "";
	}
	$users	= SIM\getUserAccounts();
	$html	= '';
	foreach($users as $user){
		$value 	= get_user_meta( $user->ID, $metaKeyBase, true );

		if (strpos($metaKey, '#') !== false){
			$value			= $value[$metaKeyName];
		}
		
		//Only show if value not set
		if(empty($value)){
			$uploader = new SIM\FILEUPLOAD\FileUpload($user->ID, $metaKey);

			$html .= "<div style='margin-top:50px;'>";
				$html .= "<strong>{$user->display_name}</strong>";
				$html .= $uploader->getUploadHtml($metaKey, $targetDir, true);
			$html .= '</div>';
		}
	}

	return $html;
}