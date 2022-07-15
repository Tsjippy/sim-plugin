<?php
namespace SIM\ADMIN;
use SIM;

/**
 * 
 * Creates a default page if it does not exist yet
 */
function createDefaultPage($options, $optionKey, $title, $content, $oldOptions, $arg=[]){

    // Only create if it does not yet exist
    if(empty($oldOptions[$optionKey]) || !is_array($oldOptions[$optionKey])){
        $pages  = [];
    }else{
        $pages  = $oldOptions[$optionKey];
    }

    if(is_array($pages) && get_post_status($pages[0]) == 'publish'){
        $options[$optionKey]    = $pages;

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
    $pages[]    = $pageId;

    //Store page id in module options
    $options[$optionKey]	= $pages;

    // Do not require page updates
    update_post_meta($pageId, 'static_content', true);
    
    return $options;
}