<?php
namespace SIM\MEDIAGALLERY;
use SIM;

const MODULE_VERSION		= '7.0.22';
//module slug is the same as grandparent folder name
DEFINE(__NAMESPACE__.'\MODULE_SLUG', strtolower(basename(dirname(__DIR__))));

add_filter('sim_module_updated', function($options, $moduleSlug){
	//module slug should be the same as grandparent folder name
	if($moduleSlug != MODULE_SLUG){
		return $options;
	}

	// Create account page
	$pageId	= SIM\getModuleOption($moduleSlug, 'mediagallery');
	// Only create if it does not yet exist
	if(!$pageId || get_post_status($pageId) != 'publish'){
		$post = array(
			'post_type'		=> 'page',
			'post_title'    => 'Media Gallery',
			'post_content'  => '[mediagallery]',
			'post_status'   => "publish",
			'post_author'   => '1'
		);
		$pageId 	= wp_insert_post( $post, true, false);

		//Store page id in module options
		$options['mediagallery']	= $pageId;

		// Do not require page updates
		update_post_meta($pageId,'static_content', true);
	}
}, 10, 2);

add_filter('display_post_states', function ( $states, $post ) { 
    
	if ( $post->ID == SIM\getModuleOption(MODULE_SLUG, 'mediagallery') ) {
		$states[] = __('Media gallery page'); 
	}

	return $states;
}, 10, 2);
