<?php
/**
 * The Template for displaying all single recipes.
 *
 * @package GeneratePress
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

get_header(); 
?>
	<div id="primary">
		<main id="main">
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
			
			<div class='print_as_pdf_div'>
				<form method='post' id='print_as_pdf_form'>
					<button type='submit' class='button' name='print_as_pdf' id='print_as_pdf'>Print this location</button>
				</form>
			</div>
		</main>
		
		<?php SIM\show_comments(); ?>
	</div>

	<?php

	get_sidebar();

	get_footer();
