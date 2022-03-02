<?php
namespace SIM\LOCATIONS;
use SIM;

/**
 * The layout specific for the page with the slug 'locations' i.e. simnigeria.org/locations.
 * Displays all the post of the location type
 * 
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

global $Modules;
global $CustomSimSettings;

//Variable containing the current locations page we are on
$paged = (get_query_var('paged')) ? get_query_var('paged') : 1;

$locations_query = new \WP_Query(array(
	'post_type'			=>'location', 
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
			if ( $locations_query->have_posts() ){
				if(is_user_logged_in()){
					$map_id = $CustomSimSettings['placesmapid'];
					echo do_shortcode("[ultimate_maps id='$map_id']");
				}
				
				$archive	= true;
				while ( $locations_query->have_posts() ) :
					$locations_query->the_post();
					include(__DIR__.'/content.php');
				endwhile;
				
				//Add pagination
				$total_pages = $locations_query->max_num_pages;

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
				//No locations to show yet
				?>
				<article id="post-<?php the_ID(); ?>" <?php post_class(); ?> <?php generate_do_microdata( 'article' ); ?>>
				<div class="no-results not-found">
					<div class="inside-article">
						<div class="entry-content">
							There are no locations submitted yet.
							Add one <a href='<?php echo add_query_arg( ['type' => 'location'], get_permalink( $Modules['frontend_posting']['publish_post_page'] ) );?>'>here</a>.
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
