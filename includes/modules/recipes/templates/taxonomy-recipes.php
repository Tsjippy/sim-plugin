<?php
namespace SIM\RECIPES;
use SIM;

/**
 * The template for displaying all recipes of a particular recipytype category.
 *
 * @package GeneratePress
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

wp_enqueue_style('sim_taxonomy_style');

global $wp_query;
if($wp_query->is_embed){
	showRecipeTax();
}else{
	if(!isset($skipHeader) or !$skipHeader)	get_header(); 
	?>
	<div id="primary" <?php generate_do_element_classes( 'content' ); ?>>
		<main id="main" class='inside-article'<?php generate_do_element_classes( 'main' ); ?>>
			<?php showRecipeTax();?>
		</main>
	</div>

	<?php
	generate_construct_sidebars();

	if(!isset($skipFooter) or !$skipFooter)	get_footer();
}

function showRecipeTax(){
	if ( have_posts() ){
		do_action('sim_before_archive', 'recipe');

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
						<?php echo apply_filters('sim-empty-taxonomy', "There are no ".get_queried_object()->slug." recipies yet.", 'recipe'); ?>
					</div>
				</div>
			</div>
		</article>
		<?php
	}
}
		

	
