<?php
namespace SIM\FRONTENDPOSTING;
use SIM;

add_action( 'init', function () {

    $taxonomies = array( 'category', 'post_tag' ); // add the 2 tax to ...
    foreach ( $taxonomies as $tax ) {
        //register_taxonomy_for_object_type( $tax, 'attachment' ); // add to post type attachment
		register_taxonomy_for_object_type( $tax, 'page' );
    }

	SIM\createTaxonomies('attachment_cat', 'attachment', 'attachments');
});

add_action('sim_before_archive', function($type){
    $pageId	= SIM\getModuleOption(MODULE_SLUG, 'publish_post_page');
	if(is_numeric($pageId)){
		if($type == 'event'){
			$text	= "Add an event to the calendar";
		}else{
			$text	= "Add a new $type";
		}

		echo "<a href='".get_permalink($pageId)."?type=$type' class='button'>$text</a><br>";
	}
});

add_filter('sim_empty_description', function($message, $post){
    $pageId	= SIM\getModuleOption(MODULE_SLUG, 'publish_post_page');
	$message	= "<div style='margin-top:10px;'>";
		$message	.= "This {$post->post_type} lacks a description.<br>";
		$message	.= "Please add one.<br>";
		$message	.= "<a href='".get_permalink($pageId)."?post_id={$post->ID}' class='button'>Add discription</a>";
	$message	.= '</div>';

	return $message;
}, 10, 2);

add_filter('sim-empty-taxonomy', function($message, $type){
	$pageId	= SIM\getModuleOption(MODULE_SLUG, 'publish_post_page');
	$message	.= "<br><a href='".get_permalink($pageId)."?type=$type' class='button'>Add a $type</a>";
	return $message;
});