<?php
namespace SIM;

// Load the js file to filter all blocks
add_action( 'enqueue_block_editor_assets', function() {
    wp_register_script(
        'sim-block-filter',
        plugins_url('blocks/build/index.js', __DIR__),
        [ 'wp-blocks', 'wp-dom', 'wp-dom-ready', 'wp-edit-post' ],
        STYLE_VERSION
    );
    wp_enqueue_script( 'sim-block-filter' );
});

// Filter block visibility
add_filter('render_block', function($blockContent, $block){
	if(
		// not on a home page
		(
			isset($block['attrs']['onlyOnHomePage']) && 
			!in_array(get_the_ID(), getModuleOption('frontpage','home_page')) && 
			!is_front_page()
		)	||
		// or not logged in
		(
			isset($block['attrs']['onlyLoggedIn']) && 
			!is_user_logged_in()
		)
	){
		return '';
	}

	// add hide on mobile class
	if(isset($block['attrs']['hideOnMobile']) && $block['attrs']['hideOnMobile']){
		return "<span class='hide-on-mobile'>$blockContent</span>";
	}

	return $blockContent;
}, 10, 2);