<?php
namespace SIM\RECIPES;
use SIM;

/**
 * The layout specific for the page with the slug 'recipes' i.e. simnigeria.org/recipes.
 * Displays all the post of the recipe type
 * 
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$paged = (get_query_var('paged')) ? get_query_var('paged') : 1;

$recipes_query = new \WP_Query(array(
	'post_type'			=>'recipe', 
	'post_status'		=>'publish', 
	'paged'           	=> $paged,
	'posts_per_page'  	=> 10 )
); 

add_filter( 'body_class', function( $classes ) {
	$newclass[] = 'categorypage';
	return array_merge( $classes, $newclass );
});

get_header(); 
?>
	<div id="primary" <?php generate_do_element_classes( 'content' ); ?>>
		<main id="main" class='inside-article'<?php generate_do_element_classes( 'main' ); ?>>
			<?php
			if ( $recipes_query->have_posts() ){
				$archive	= true;
				while ( $recipes_query->have_posts() ) : 
					$recipes_query->the_post();
					include(__DIR__.'/content.php');
				endwhile;
				
				//Add pagination
				$total_pages = $recipes_query->max_num_pages;

				if ($total_pages > 1){
					$current_page = max(1, get_query_var('paged'));

					echo paginate_links(array(
						'base' 		=> get_pagenum_link(1) . '%_%',
						'format' 	=> '/page/%#%',
						'current' 	=> $current_page,
						'total' 	=> $total_pages,
						'prev_text' => __('« prev'),
						'next_text' => __('next »'),
					));
				}
			}else{
				//No recipes to show yet
				?>
				<article id="post-<?php the_ID(); ?>" <?php post_class(); ?> <?php generate_do_microdata( 'article' ); ?>>
				<div class="no-results not-found">
					<div class="inside-article">
						<div class="entry-content">
							There are no recipes submitted yet.
							Add one <a href='<?php echo add_query_arg( ['type' => 'recipe'], get_permalink( SIM\get_module_option('frontend_posting', 'publish_post_page') ) );?>'>here</a>.
						</div>
					</div>
				</div>
				</article>
				<?php
			}

			?>
		</main>
	</div>

	<?php

	generate_construct_sidebars();

	get_footer();
