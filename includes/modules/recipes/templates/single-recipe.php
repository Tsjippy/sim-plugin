<?php
namespace SIM\RECIPES;
use SIM;

/**
 * The Template for displaying all single recipes.
 *
 * @package GeneratePress
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if(!isset($skipHeader) || !$skipHeader){
	get_header(); 
}
?>

	<div id="primary">
		<main id="main">
			<style>
				@media (min-width: 991px){
					#primary:not(:only-child){
						width: 70%;
					}
				}
			</style>
			<?php
			$archive	= false;
			while ( have_posts() ) :
				the_post();

				include(__DIR__.'/content.php');	

			endwhile;
			
			?> <nav id='post_navigation'>
				<span id='prev'>
					<?php previous_post_link(); ?>
				</span>
				<span id='next' style='float:right;'>
					<?php next_post_link(); ?>
				</span>
			</nav>
			
			<?php
			echo apply_filters('sim-single-template-bottom', '', 'recipe');
			?>
		</main>
		
		<?php generate_do_comments_template( 'single' ); ?>
	</div>

	<?php

	generate_construct_sidebars();

	if(!isset($skipFooter) or !$skipFooter)	get_footer();
