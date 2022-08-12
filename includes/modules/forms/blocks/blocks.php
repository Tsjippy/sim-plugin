<?php
namespace SIM\FORMS;
use SIM;

add_action('init', function () {
	register_block_type(
		__DIR__ . '/formselector/build',
		array(
			'render_callback' => __NAMESPACE__.'\showFormSelector',
		)
	);
});