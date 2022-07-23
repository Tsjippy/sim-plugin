<?php
namespace SIM\EVENTS;
use SIM;

add_action('init', function () {
	register_block_type(
		__DIR__ . '/../blocks/upcomingEvents/build',
		array(
			'render_callback' => __NAMESPACE__.'\displayUpcomingEvents',
		)
	);
});

function displayUpcomingEvents($attributes, $content) {

	$args = wp_parse_args($attributes, array(
		'items' 		=> 1,
		'months'		=> 2,
		'categories'	=> [],
		'home'			=> false
	));

	if($args['home'] && (!in_array(get_the_ID(), SIM\getModuleOption(MODULE_SLUG,'home_page')) && !is_front_page())){
		return "";
	}

	$categories	= get_categories( array(
		'taxonomy'		=> 'events',
		'hide_empty' 	=> false,
	) );

	$exclude	= $args['categories'];

	$include	= [];

	foreach($categories as $category){
		if(!isset($exclude[$category->term_id])){
			$include[]	= $category->term_id;
		}
	}
	
	$events		= new DisplayEvents();
	return $events->upcomingEvents($args['items'], $args['months'], $include);
}