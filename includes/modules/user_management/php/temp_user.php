<?php
namespace SIM;

//Add expiry data column to users screen
add_filter( 'manage_users_columns', 'SIM\add_expiry_date_to_user_table' );
function add_expiry_date_to_user_table( $columns ) {
    $columns['expiry_date'] = 'Expiry Date';
    return $columns;
}

//Add content to the expiry data column
add_filter( 'manage_users_custom_column', 'SIM\add_expiry_date_to_user_table_row', 10, 3 );
function add_expiry_date_to_user_table_row( $val, $column_name, $user_id ) {
    if($column_name != 'expiry_date')   return $val;
    return get_user_meta( $user_id, 'account_validity',true);
}

add_filter( 'manage_users_sortable_columns', function ( $columns ) {
    $columns['expiry_date'] = 'Expiry Date';

    return $columns;
} );