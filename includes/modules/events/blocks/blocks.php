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
		__DIR__ . '/upcomingArrivals/build',
		array(
			'render_callback' => __NAMESPACE__.'\upcomingArrivalsBlock',
			"attributes"	=>  [
				"title"	=> [
					"type"		=> "string",
					"default"	=> ''
				],
				'months'	=> [
					'type'		=> 'integer',
					'default'	=> 2
				]
			]
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

function upcomingArrivalsBlock($attributes){
	$args = wp_parse_args($attributes, array(
		'title' 		=> '',
		'months'		=> 2
	));

	$arrivingUsers	= get_users( [
		'meta_query' => array(
			'relation' => 'AND',
			array(
				'key'     => 'arrival_date',
				'value'   => date("Y-m-d"),
				'compare' => '>=',
				'type'    => 'DATE'
			),
			array(
				'key'     => 'arrival_date',
				'value'   => date("Y-m-d", strtotime("+{$args['months']} month", time())),
				'compare' => '<=',
				'type'    => 'DATE'
			)
		),
		'orderby'	=> 'meta_value',
		'order' 	=> 'ASC'
	]);

	//Loop over the arrival_users to find any families
	$skip	= [];
	$dates	= [];
	foreach($arrivingUsers as $user){
		if(in_array($user->ID, $skip)){
			continue;
		}

		$partnerId	= SIM\hasPartner($user->ID);
		$name		= $user->display_name;

		if($partnerId){
			$skip[]		= $partnerId;
			$partner	= get_userdata($partnerId);

			if($partner->last_name == $user->last_name){
				$name 	= $user->last_name.' family';
			}else{
				$name	= $user->display_name.' & '. $partner->display_name;
			}
			
		}

		$url 	= SIM\maybeGetUserPageUrl($user->ID);

		$dateString	= date('d-m-Y', strtotime(get_user_meta($user->ID, 'arrival_date', true)));

		// Add to an existing date, multiple people arrive on the same date
		if(isset($dates[$dateString])){
			$dates[$dateString]	.= "<br><a href='$url' class='arrival-name'>$name</a>";
		}else{
			$dates[$dateString]	= "<a href='$url' class='arrival-name'>$name</a>";
		}
	}

	$html	= "<h4>{$args['title']}</h4>";
	$html	.= "<div class='arrival-dates-wrapper'>";
		foreach($dates as $date=>$string){
			$html	.= "<div class='arrival-date-wrapper'>";
				$html	.= "<strong class='arrival-title'>$date</strong><br>$string";
			$html	.= "</div>";
		}
	$html	.= "</div>";

	return $html;
}