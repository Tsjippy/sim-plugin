<?php
namespace SIM\BANKING;
use SIM;

/**
 * Test import of account statments
 * Make sure the file \wp-content\uploads\Account-Statement.rtf exists
 */
function testMailImport(){
    // Insert the post into the database.
    $post   = array(
        'post_title'    => 'My post',
        'post_content'  => 'AccountID: 220000-65-050556-062',
        'post_status'   => 'publish'
    );

    $post['ID'] = wp_insert_post($post );

    wp_insert_attachment(
        array(
            'post_title'        => 'account-statement',
            'post_content'      => 'test',
            'post_mime_type'    => 'application/msword',
        ),
        "Account Statement.rtf",
        $post['ID']
    );

    postieBeforeFilter($post);
}