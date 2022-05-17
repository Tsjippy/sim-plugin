<?php
namespace SIM\EMBEDPAGE;
use SIM;

// make it possible to include a page in another page
add_shortcode('embed_page', function($atts){
	global $wp_query;

    $oldQuery   = $wp_query;

	ob_start();

	$id		= explode('/', $atts['id']);
	// post
	if(is_numeric($id[0])){
        $args = array(
            'post_type' => 'any',	
            'post__in' => array($id[0])
        );
        $wp_query = new \WP_Query( $args );

        if ( $wp_query->have_posts() ) {
            while ( $wp_query->have_posts() ) {
                $wp_query->the_post();
                the_content();
            }
        }
	// category or archive
	}else{
        if(count($id) == 2){
            $args = array(
                'post_type' => rtrim($id[0], 's'),	
                'tax_query' => array(
                    array (
                        'taxonomy' => $id[0],
                        'field' => 'slug',
                        'terms' => $id[1],
                    )
                ),
            );
            $type   = 'taxonomy';
        }else{
            // archive
            $args   = array( 'taxonomy' => $id[0] );
            $type   = 'archive';
        }   
		
        $wp_query   = new \WP_Query($args);
        $wp_query->is_embed = true;
		$template           = SIM\getTemplateFile('', $type, $id[0]);

		include_once($template);
	}

    $wp_query   = $oldQuery;

    return ob_get_clean();
});