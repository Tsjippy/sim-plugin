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
	
	$events		= new DisplayEvents();
	return $events->upcomingEvents();
}