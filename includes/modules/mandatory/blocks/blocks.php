<?php
namespace SIM\MANDATORY;
use SIM;

add_action('init', function () {
	register_block_type(
		__DIR__ . '/mandatory-pages-overview/build',
		array(
			'render_callback' => __NAMESPACE__.'\mustReadDocuments',
		)
	);
});

add_action( 'enqueue_block_assets', function(){
    if(is_admin()){
        registerMandatoryScripts();

        wp_enqueue_script( 'sim_mandatory_script');

        wp_enqueue_script(
            'sim-mandatory-block',
            plugins_url('blocks/mandatory-settings/build/index.js', __DIR__),
            [ 'wp-blocks', 'wp-dom', 'wp-dom-ready', 'wp-edit-post' ],
            MODULE_VERSION
        );

        $postId		= get_the_ID();

        $audience   = get_post_meta($postId, 'audience', true);
        if(!is_array($audience) && !empty($audience)){
            $audience  = json_decode($audience, true);
        }

        wp_localize_script(
            'sim-mandatory-block',
            'mandatory',
            getAudienceOptions($audience, $postId)
        );
    }
});

// register custom meta tag field
add_action( 'init', function(){
	register_post_meta( '', 'audience', array(
        'show_in_rest' 	    => true,
        'single' 		    => true,
        'type' 			    => 'string',
		'default'			=> '{}',
		'sanitize_callback' => 'sanitize_text_field'
    ) );
} );