<?php
namespace SIM\ADMIN;
use SIM;

/**
 * 
 * Creates a default page if it does not exist yet
 */
function createDefaultPage($options, $optionKey, $title, $content, $oldOptions, $arg=[]){
    // Only create if it does not yet exist
    if(!empty($oldOptions[$optionKey]) && get_post_status($oldOptions[$optionKey]) == 'publish'){
        $options[$optionKey]    = $oldOptions[$optionKey];

        return $options;
    }

    // Create account page
    $post = array(
        'post_type'		=> 'page',
        'post_title'    => $title,
        'post_content'  => $content,
        'post_status'   => "publish",
        'post_author'   => '1'
    );

    if(!empty($arg)){
        $post   = array_merge($post, $arg);
    }
    $pageId 	= wp_insert_post( $post, true, false);

    //Store page id in module options
    $options[$optionKey]	= $pageId;

    // Do not require page updates
    update_post_meta($pageId, 'static_content', true);
    

    return $options;
}