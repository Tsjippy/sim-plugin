<?php
namespace SIM\BULKCHANGE;
use SIM;

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
	if($user->ID != 0 and array_intersect($allowedRoles, $user->roles ) ) {
		//Load js
		wp_enqueue_script('sim_table_script');
		
		$html = "
			<h2 id='{$metaKey}_table_title' class='table_title'>$displayName</h2>
			<table class='sim-table' data-url='bulkchange\bulk_change_meta'>
				<thead>
					<tr>
						<th class='dsc'>Name</th>
						<th id='$metaKey'>$displayName</th>
					</tr>
				</thead>";
		
		//Get all users who are non-local nigerias, sort by last name
		foreach(SIM\getUserAccounts($returnFamily) as $user){
			$value 	= get_user_meta( $user->ID, $metaKeyBase, true );
			
			//Check if the value is an array
			if(is_array($value))	$value 	= $value[$metaKeyName];
			
			//Now the value should not be an array
			if(is_array($value)) return 'please provide single value not an array';
				
			if($value == "") $value = "Click to update";			
			
			//Add html
			$html .= "<tr class='table-row' data-meta_key='$metaKey'>
						<td>$user->displayName</td>
						<td class='edit' data-user_id='$user->ID'>$value</td>
					  </tr>";
		}
		$html .= '</table>';
		return $html;	
	}

	return '<p>You do not have permission to view this</p>';
}
