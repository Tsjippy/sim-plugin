<?php
namespace SIM;

add_action('init', function () {
	register_block_type(
		__DIR__ . '/show_categories/build',
		array(
			'render_callback' => __NAMESPACE__.'\displayCategories',
		)
	);
});

function displayCategories($attributes) {

	$args = wp_parse_args($attributes, array(
		'count' 		=> false
	));

	if(is_home()){
		$taxonomy	= 'category';
	}elseif(is_archive()){

		if(isset(get_queried_object()->taxonomy)){
			$taxonomy	= get_queried_object()->taxonomy;
		}else{
			$taxonomy	= get_queried_object()->taxonomies[0];
		}
	}elseif(is_tax()){
		$taxonomy	= '';
	}

	return	'<style> .widget li {list-style-type: none;}</style>'.wp_list_categories( array(
		'echo'				=> 0,
		'taxonomy' 			=> $taxonomy,
		'current_category'	=> get_queried_object()->term_id,
		'show_count'		=> $args['count'],
		'title_li' 			=> '<h4>' . __( 'Categories', 'sim' ) . '</h4>'
	));
}