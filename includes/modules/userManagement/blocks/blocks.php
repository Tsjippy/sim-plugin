<?php
namespace SIM\USERMANAGEMENT;
use SIM;

add_action('init', function () {
	register_block_type(
		__DIR__ . '/reminders/build',
		array(
			'render_callback' => __NAMESPACE__.'\expiryWarnings',
		)
	);
});