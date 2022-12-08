<?php
namespace SIM\EMBEDPAGE;
use SIM;

// make it possible to include a page in another page
add_shortcode('embed_page', function($atts){
    if(!is_array($atts) || isset($atts['id']) || !is_numeric($atts['id']) ){
        return '';
    }

	$id		= explode('/', $atts['id']);

    return displayPageContents($id);
});

function displayPageContents($id, $collapsible=false){
    global $wp_query;

    $oldQuery   = $wp_query;

	ob_start();

    // post
    if(is_numeric($id)){
        $args = array(
            'post_type' => 'any',
            'post__in' => array($id)
        );
        $wp_query = new \WP_Query( $args );

        if ( $wp_query->have_posts() ) {
            while ( $wp_query->have_posts() ) {
                $wp_query->the_post();

                if($collapsible){
                    ?>
                    <span class='small content-embed-toggle'>
                        <span class='underline'>
                            <?php
                            the_title();
                            ?>
                            <span class='icon'>
                                ▼
                            </span>
                        </span>
                    
                        <div class='content-embed hidden'>
                            <?php
                            the_content();
                            ?>
                        </div>
                    </span>
                    <?php
                }else{
                    the_content();
                }

            }
        }
    // category or archive
    }elseif(is_array($id)){
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
}