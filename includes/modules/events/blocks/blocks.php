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
					"type"		=> "object",
					"default"	=> [
						"move"		=> true,
						"remove"	=> true
					]
				],
				'event'	=> [
					'type'		=> 'string',
					'default'	=> ''
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
	register_post_meta( 'event', 'eventdetails', array(
        'show_in_rest' 	=> true,
        'single' 		=> true,
        'type' 			=> 'string',
		'sanitize_callback' => 'sanitize_text_field'
    ) );
} );

add_action( 'added_post_meta', __NAMESPACE__.'\createEvents', 10, 4);
add_action( 'updated_postmeta', __NAMESPACE__.'\createEvents', 10, 4);

function createEvents($metaId, $postId,  $metaKey,  $metaValue ){
	if($metaKey != 'eventdetails' || empty($metaValue)){
		return;
	}

	$metaValue	= json_decode($metaValue, true);

	$events = new CreateEvents();
	$events->postId	= $postId;
    //check if anything has changed
	$events->removeDbRows();
	
	//create events
	$events->eventData		= $metaValue;
	$result					= $events->createEvents();

	if(is_wp_error($result)){
		SIM\printArray($result);
	}
}