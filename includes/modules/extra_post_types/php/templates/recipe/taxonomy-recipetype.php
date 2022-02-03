<?php
/**
 * The template for displaying all recipes of a particular recipytype category.
 *
 * @package GeneratePress
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

global $Modules;

add_filter( 'body_class', function( $classes ) {
	$newclass[] = 'categorypage';
	return array_merge( $classes, $newclass );
});

get_header(); ?>
	<div id="primary" <?php generate_do_element_classes( 'content' ); ?>>
		<main id="main" class='inside-article'<?php generate_do_element_classes( 'main' ); ?>>
			<?php

			if ( have_posts() ){
				$archive	= true;
				while ( have_posts() ) :
					the_post();
					include(__DIR__.'/content.php');

				endwhile;
				the_posts_pagination();
			}else{
				//No recipes with this category
				?>
				<article id="post-<?php the_ID(); ?>" <?php post_class(); ?> <?php generate_do_microdata( 'article' ); ?>>
				<div class="no-results not-found">
					<div class="inside-article">
						<div class="entry-content">
							There are no <?php echo get_queried_object()->slug;?> recipies yet.
							Add one <a href='<?php echo add_query_arg( ['type' => 'recipe'], get_permalink( $Modules['frontend_posting']['publish_post_page'] ) );?>'>here</a>.
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
