<?php
namespace SIM\LOCATIONS;
use SIM;
/**
 * The template for displaying all recipes of a particular recipytype category.
 *
 * @package GeneratePress
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

add_filter( 'body_class', function( $classes ) {
	$newclass[] = 'categorypage';
	return array_merge( $classes, $newclass );
});

get_header(); ?>

	<div id="primary" <?php generate_do_element_classes( 'content' ); ?>>
		<main id="main" class='inside-article'<?php generate_do_element_classes( 'main' ); ?>>
			<?php
			$name 				= get_queried_object()->slug;
			if ( have_posts() ){
				//only show the map if logged in
				if(is_user_logged_in()){
					//Show the map of this category
					echo "<div style='margin-bottom:25px;'>";
						$map_name			= $name."_map";
						$map_id				= SIM\get_module_option('locations', $map_name);

						if(is_numeric($map_id)){
							echo do_shortcode("[ultimate_maps id='$map_id']");
						}
					echo '</div>';
				}
			
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
							There are no <?php echo $name;?> locations yet.
							Add one <a href='<?php echo add_query_arg( ['type' => 'location'], get_permalink( SIM\get_module_option('frontend_posting', 'publish_post_page') ) );?>'>here</a>.
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
