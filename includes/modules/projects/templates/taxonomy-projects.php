<?php
namespace SIM\PROJECTS;
use SIM;
/**
 * The template for displaying all items of a particular category.
 *
 * @package GeneratePress
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

wp_enqueue_style('sim_taxonomy_style');

global $post;
global $wp_query;

if($wp_query->is_embed){
	$skipWrapper	= true;
}

if($skipWrapper){
	displayProjectTax();
}else{
	if(!isset($skipHeader) || !$skipHeader){
		get_header(); 
	}
	?>
	<div id="primary" <?php generate_do_element_classes( 'content' ); ?>>
		<main id="main" class='taxonomy inside-article'<?php generate_do_element_classes( 'main' ); ?>>
			<?php displayProjectTax();?>
		</main>
	</div>
	<?php

	if(!isset($skipFooter) || !$skipFooter){
		get_footer();
	}
}

function displayProjectTax(){
	$name 				= get_queried_object()->slug;
	if ( have_posts() ){
		do_action('sim_before_archive', 'project');
	
		while ( have_posts() ) :
			the_post();
			include(__DIR__.'/content.php');
		endwhile;
		the_posts_pagination();
	}else{
		//No items with this category
		?>
		<article id="post-<?php the_ID(); ?>" <?php post_class(); ?> <?php generate_do_microdata( 'article' ); ?>>
		<div class="no-results not-found">
			<div class="inside-article">
				<div class="entry-content">
					<?php echo apply_filters('sim-empty-taxonomy', "There are no $name projects yet", 'project'); ?>
				</div>
			</div>
		</div>
		</article>
		<?php
	}
}