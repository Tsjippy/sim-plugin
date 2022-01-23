<?php
//Add expiry data column to users screen
add_filter( 'manage_users_columns', 'SIM\add_expiry_date_to_user_table' );
function add_expiry_date_to_user_table( $column ) {
    $column['expiry_date'] = 'Expiry Date';
    return $column;
}

//Add content to the expiry data column
add_filter( 'manage_users_custom_column', 'SIM\add_expiry_date_to_user_table_row', 10, 3 );
function add_expiry_date_to_user_table_row( $val, $column_name, $user_id ) {
    switch ($column_name) {
        case 'expiry_date' :
            return get_user_meta( $user_id, 'account_validity',true);
        default:
    }
    return $val;
}

add_filter( 'manage_users_sortable_columns', 'SIM\make_expiry_date_sortable' );
function make_expiry_date_sortable( $columns ) {
    $columns['expiry_date'] = 'Expiry Date';

    return $columns;
}