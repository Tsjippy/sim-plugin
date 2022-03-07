<?php
namespace SIM\USERMANAGEMENT;
use SIM;

// Only load when option is activated
if(!SIM\get_module_option('user_management', 'tempuser')) return;

//Add expiry data column to users screen
add_filter( 'manage_users_columns', 'SIM\add_expiry_date_to_user_table' );
function add_expiry_date_to_user_table( $columns ) {
    $columns['expiry_date'] = 'Expiry Date';
    return $columns;
}

//Add content to the expiry data column
add_filter( 'manage_users_custom_column', function ( $val, $column_name, $user_id ) {
    if($column_name != 'expiry_date')   return $val;
    return get_user_meta( $user_id, 'account_validity',true);
}, 10, 3);

add_filter( 'manage_users_sortable_columns', function ( $columns ) {
    $columns['expiry_date'] = 'Expiry Date';

    return $columns;
} );