<?php
namespace SIM\FORMS;
use SIM;

add_action( 'rest_api_init', function () {
	//Route for notification messages
	register_rest_route( 
		'sim/v1', 
		'/save_table_prefs', 
		array(
			'methods' 				=> \WP_REST_Server::EDITABLE,
			'callback' 				=> __NAMESPACE__.'\save_table_prefs',
			'permission_callback' 	=> '__return_true',
			'args'					=> array(
				'formname'		=> array('required'	=> true),
				'column_name'	=> array('required'	=> true),
			)
		)
	);

	register_rest_route( 
		'sim/v1', 
		'/delete_table_prefs', 
		array(
			'methods' 				=> \WP_REST_Server::EDITABLE,
			'callback' 				=> __NAMESPACE__.'\delete_table_prefs',
			'permission_callback' 	=> '__return_true',
			'args'					=> array(
				'formname'	=> array('required'	=> true)
			)
		)
	);
} );

function save_table_prefs( \WP_REST_Request $request ) {
	if (is_user_logged_in()) {
		$columnName		= $request['column_name'];

		$user_id		= get_current_user_id();
		$hidden_columns	= (array)get_user_meta($user_id, 'hidden_columns_'.$request['formname'], true);	

		$hidden_columns[$columnName]	= 'hidden';

		update_user_meta($user_id, 'hidden_columns_'.$request['formname'], $hidden_columns);	

		return 'Succesfully updated column settings';
	}else{
		return 'You are not logged in';
	}
}

function delete_table_prefs( \WP_REST_Request $request ) {
	if (is_user_logged_in()) {
		$user_id		= get_current_user_id();
		delete_user_meta($user_id, 'hidden_columns_'.$request['formname']);	

		return 'Succesfully deleted column settings';
	}else{
		return 'You are not logged in';
	}
}