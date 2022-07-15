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

/**
 * Add categories to attachment page
 * 
 * WP expects a comma seperated list of cat ids, so
 * we create a checkbox who update a hidden input wiht the comma seperated checkboxes
 */

add_filter( 'attachment_fields_to_edit', function($formFields, $post ){
    $categories	= get_categories( array(
		'orderby' 		=> 'name',
		'order'   		=> 'ASC',
		'taxonomy'		=> 'attachment_cat',
		'hide_empty' 	=> false,
	) );

	$checkboxes		= '';
	$catIds			= '';
	foreach($categories as $category){
		$name 				= ucfirst($category->slug);
		$catId 				= $category->cat_ID;
		$checked			= '';
		$taxonomy			= $category->taxonomy;
		
		//if this cat belongs to this post
		if(has_term($catId, $taxonomy, $post->ID)){
			$checked 	 = 'checked';
			if(!empty($catIds)){
				$catIds	.= ',';
			}
			$catIds		.= $catId;
		}

		$checkboxes	.= "<input $checked style='width: initial' type='checkbox' class='attachment_cat_checkbox' value='$catId' onchange='attachmentChanged(this)'>";
		$checkboxes	.= $name;
	}

    $html   = "<div class='attachment_cat_wrapper'>";
		$html	.= "<script>";
			$html	.= "function attachmentChanged(element){";
				$html	.= "let val		= element.value;";
				$html	.= "let catEl	= document.getElementById('attachments[{$post->ID}][attachment_cat]');";
				$html	.= "if(element.checked){";
					$html	.= "catEl.value	= catEl.value + ','+val;";
				$html	.= "} else{";
					$html	.= "catEl.value	= catEl.value.replace(','+val, '');";
				$html	.= "}";
			$html	.= "}";
		$html	.= "</script>";
		$html	.= "<input type='hidden' name='attachments[{$post->ID}][attachment_cat]' id='attachments[{$post->ID}][attachment_cat]' value='$catIds'>";
        $html   .= $checkboxes;
    $html   .= "</div>";

    $formFields['attachment_cat']['input']	= 'html';
    $formFields['attachment_cat']['html']	= $html;
	$formFields['attachment_cat']['label']	= 'Categories';

    return $formFields;
}, 10, 2);

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