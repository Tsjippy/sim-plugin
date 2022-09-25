<?php
namespace SIM\LOCATIONS;
use SIM;

/**
 * The Template for displaying all single locations
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if(!isset($skipHeader) || !$skipHeader){
	get_header();
}
?>
	<div id="primary">
		<style>
			@media (min-width: 991px){
				#primary:not(:only-child){
					width: 70%;
				}
			}
		</style>
		<main id="main">
			<?php
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
			echo apply_filters('sim-single-template-bottom', '', 'location');
			?>
		</main>
		
		<?php SIM\showComments(); ?>
	</div>

	<?php

	get_sidebar();

	if(!isset($skipFooter) || !$skipFooter){
		get_footer();
	}
