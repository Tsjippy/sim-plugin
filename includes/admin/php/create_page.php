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

    if(is_array($pages)){
        foreach($pages as $key=>$page){
			if(get_post_status($page) != 'publish'){
				unset($pages[$key]);
			}
		}

        $options[$optionKey]    = $pages;

        if(!empty($pages)){
            return $options;
        }
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

/**
 * Checks if all pages are valid in the default pages option array and returns the first valid one as a link
 * 
 * @param   string  $optionKey      The ky in the module option array
 * @param   string  $moduleSlug     The slug of the module
 * 
 * @return  string                  The url
 */
function getDefaultPageLink($optionKey, $moduleSlug){
    global $Modules;

    $url		= '';
	$pageIds	= $Modules[$moduleSlug][$optionKey];

	if(is_array($pageIds)){
		foreach($pageIds as $key=>$pageId){
			if(get_post_status($pageId) != 'publish'){
				unset($pageIds[$key]);
			}
		}

        $pageIds    = array_values($pageIds);
		if(!empty($pageIds)){
			$url		= get_permalink($pageIds[0]);
		}

        if($Modules[$moduleSlug][$optionKey] != $pageIds){
		    $Modules[$moduleSlug][$optionKey]	= $pageIds;
		    update_option('sim_modules', $Modules);
        }
	}

    return $url;
}