<?php
namespace SIM\EVENTS;
use SIM;

add_action('init', function () {
	register_block_type(
		__DIR__ . '/upcomingEvents/build',
		array(
			'render_callback' => __NAMESPACE__.'\displayUpcomingEvents',
		)
	);

	register_block_type(
		__DIR__ . '/schedules/build',
		array(
			'render_callback' => __NAMESPACE__.'\displaySchedules',
		)
	);

	register_block_type(
		__DIR__ . '/metadata/build',
		array(
			"attributes"	=>  [
				"lock"	=> [
					"type"	=> "object",
					"default"	=> [
						"move"		=> true,
						"remove"	=> true
					]
				],
				'event'	=> [
					'type'	=> 'string',
					'default'	=> 'hoi daar'
				] 
			]
		)
	);
});

function displayUpcomingEvents($attributes) {

	$args = wp_parse_args($attributes, array(
		'items' 		=> 10,
		'months'		=> 3,
		'categories'	=> []
	));

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

function displaySchedules(){
	$schedule	= new Schedules();
	return $schedule->showSchedules();
}

// register custom meta tag field
add_action( 'init', function(){
	register_post_meta( 'event', 'event', array(
        'show_in_rest' 	=> true,
        'single' 		=> true,
        'type' 			=> 'string',
		'sanitize_callback' => function($text) {
            return sanitize_text_field($text);
        }
    ) );
} );
