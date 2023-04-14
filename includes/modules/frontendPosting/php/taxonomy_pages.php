<?php
namespace SIM\FRONTENDPOSTING;
use SIM;

// filter library if needed
add_filter( 'ajax_query_attachments_args', function($query){
    if(!empty($_REQUEST['query']['category'])){
        $category = $_REQUEST['query']['category'];

		$query['tax_query'] = array(
			array (
				'taxonomy' 	=> 'attachment_cat',
				'field' 	=> 'slug',
				'terms' 	=> $category ,
			)
		);
    }

    return $query;
} );

add_action( 'init', function () {

    $taxonomies = array( 'category', 'post_tag' ); // add the 2 tax to ...
    foreach ( $taxonomies as $tax ) {
		register_taxonomy_for_object_type( $tax, 'page' );
    }

	SIM\createTaxonomies('attachment_cat', 'attachment', 'attachments');
});

/**
 * Add categories to attachment page
 *
 * WP expects a comma seperated list of cat slugs, so
 * we create a checkbox who update a hidden input wiht the comma seperated checkboxes
 */

add_filter( 'attachment_fields_to_edit', function($formFields, $post ){
    $categories	= get_categories( array(
		'orderby' 		=> 'name',
		'order'   		=> 'ASC',
		'taxonomy'		=> 'attachment',
		'hide_empty' 	=> false,
	) );

	$checkboxes		= '';
	$catNames			= '';
	foreach($categories as $category){
		$name 				= str_replace('-', ' ', ucfirst($category->slug));
		$catId 				= $category->cat_ID;
		$checked			= '';
		$taxonomy			= $category->taxonomy;
		
		//if this cat belongs to this post
		if(has_term($catId, $taxonomy, $post->ID)){
			$checked 	 = 'checked';
			if(!empty($catNames)){
				$catNames	.= ',';
			}
			$catNames		.= $category->slug;
		}

		$checkboxes	.= "<label>";
			$checkboxes	.= "<input $checked style='width: initial' type='checkbox' class='attachment_cat_checkbox' value='{$category->slug}' onchange='attachmentChanged(this)'>";
			$checkboxes	.= $name;
		$checkboxes	.= "</label><br>";
	}

    $html   = "<div class='attachment_cat_wrapper'>";
		$html	.= "<script>";
			$html	.= "function attachmentChanged(element){";
				$html	.= "let val		= element.value;";
				$html	.= "let catEl	= document.getElementById('attachments[{$post->ID}][attachment_cat]');";
				$html	.= "if(element.checked){";
					$html	.= "catEl.value	= catEl.value + ','+val;";
				$html	.= "} else{";
					$html	.= "catEl.value	= catEl.value.replace(','+val, '').replace(val, '');";
				$html	.= "}";
			$html	.= "}";
		$html	.= "</script>";
		$html	.= "<input type='hidden' name='attachments[{$post->ID}][attachment_cat]' id='attachments[{$post->ID}][attachment_cat]' value='$catNames'>";
        $html   .= $checkboxes;
    $html   .= "</div>";

    $formFields['attachment_cat']['input']	= 'html';
    $formFields['attachment_cat']['html']	= $html;
	$formFields['attachment_cat']['label']	= 'Categories';

    return $formFields;
}, 10, 2);

add_action('sim_before_archive', function($type){
    $url			= SIM\ADMIN\getDefaultPageLink(MODULE_SLUG, 'front_end_post_pages');
	if(is_numeric($url)){
		if($type == 'event'){
			$text	= "Add an event to the calendar";
		}else{
			$text	= "Add a new $type";
		}

		echo "<a href='$url?type=$type' class='button'>$text</a><br>";
	}
});

add_filter('sim_empty_description', function($message, $post){
    $url			= SIM\ADMIN\getDefaultPageLink(MODULE_SLUG, 'front_end_post_pages');
	$message	= "<div style='margin-top:10px;'>";
		$message	.= "This {$post->post_type} lacks a description.<br>";
		$message	.= "Please add one.<br>";
		$message	.= "<a href='$url?post_id={$post->ID}' class='button'>Add description</a>";
	$message	.= '</div>';

	return $message;
}, 10, 2);

add_filter('sim-empty-taxonomy', function($message, $type){
	$url			= SIM\ADMIN\getDefaultPageLink(MODULE_SLUG, 'front_end_post_pages');
	$message	.= "<br><a href='$url?type=$type' class='button'>Add a $type</a>";
	return $message;
}, 10, 2);