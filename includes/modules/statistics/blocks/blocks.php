<?php
namespace SIM\STATISTICS;
use SIM;

add_action('init', function () {
	register_block_type(
		__DIR__ . '/statistics/build',
		array(
			'render_callback' => __NAMESPACE__.'\statisticsWidget',
		)
	);
});