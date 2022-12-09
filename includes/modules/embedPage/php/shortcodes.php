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

function displayPageContents($id, $collapsible=false, $linebreak=false){
    global $wp_query;

	ob_start();

    // post
    if(is_numeric($id)){
        $post       = get_post($id);

        if ( !empty($post)) {
            $content    = get_the_content( null, false, $post );
            $content    = apply_filters( 'the_content', $content );
            $content    = str_replace( ']]>', ']]&gt;', $content );

            if($collapsible){
                if($linebreak){
                    echo '<br>';
                }
                ?>
                <span class='small content-embed-toggle'>
                    <span class='underline'>
                        <a href="<?php the_permalink($post); ?>" title="<?php the_title_attribute(['post' => $post]); ?>"><?php echo get_the_title( $post ); ?></a>
                        <span class='icon'>
                            ▼
                        </span>
                    </span>
                
                    <div class='content-embed hidden'>
                        <?php
                        echo $content;
                        ?>
                    </div>
                </span>
                <?php
            }else{
                echo $content;
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

        $oldQuery   = $wp_query;
        $wp_query   = new \WP_Query($args);
        $wp_query->is_embed = true;
        $template           = SIM\getTemplateFile('', $type, $id[0]);

        include_once($template);

        $wp_query   = $oldQuery;
    }

    return ob_get_clean();
}