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
});