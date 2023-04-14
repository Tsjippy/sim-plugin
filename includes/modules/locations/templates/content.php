<?php
namespace SIM\LOCATIONS;
use SIM;

/**
 * The content of a location shared between a single post, archive or the recipes page.
**/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$archive	= false;
if(is_tax() || is_archive()){
	$archive	= true;
}

?>
<style>
	.metas{
		margin-top:10px;
		display: flex;
		flex-wrap: wrap;
	}

	.location.meta{
		margin-right: 10px;
	}

	.cat_card{
		padding: 10px;
	}
</style>
<article id="post-<?php the_ID(); ?>" <?php post_class(); ?> <?php generate_do_microdata( 'article' ); ?>>
	<div class="cat_card<?php if($archive){echo ' inside-article';}?>">
		
		<?php
		if($archive){
			$url = get_permalink(get_the_ID());
			echo the_title( "<h3 class='archivetitle'><a href='$url'>", '</a></h3>' );
		}else{
			do_action( 'sim_before_content');
		}
		?>
		<div class='entry-content<?php if($archive){echo ' archive';}?>'>
			<?php
			if(is_user_logged_in()){
			?>
				<div class='author'>
					Shared by: <a href='<?php echo SIM\maybeGetUserPageUrl(get_the_author_meta('ID')) ?>'><?php the_author(); ?></a>
				</div>
				<?php
				if($archive){
					?>
					<div class='picture' style='margin-top:10px;'>
						<?php
						the_post_thumbnail([250,200]);
						?>
					</div>
					<?php
				}
			}
			?>

			<div class='location metas'>
				<div class='category location meta'> 
					<?php
					$categories = wp_get_post_terms(
						get_the_ID(), 
						'locations',
						array(
							'orderby'   => 'name',
							'order'     => 'ASC',
							'fields'    => 'id=>name'
						) 
					);
					
					$url	= plugins_url('pictures/location.png', __DIR__);
					echo "<img src='$url' alt='category' loading='lazy' class='location_icon'>";
					
					//First loop over the cat to see if any parent cat needs to be removed
					foreach($categories as $id=>$category){
						//Get the child categories of this category
						$children = get_term_children($id,'locations');
						
						//Loop over the children to see if one of them is also in he cat array
						foreach($children as $child){
							if(isset($categories[$child])){
								unset($categories[$id]);
								break;
							}
						}
					}
					
					//now loop over the array to print the categories
					$lastKey	 = array_key_last($categories);
					foreach($categories as $id=>$category){
						//Only show the category if all of its subcats are not there
						$url = get_term_link($id);
						$category = ucfirst($category);
						echo "<a href='$url'>$category</a>";
						
						if($id != $lastKey){
							echo ', ';
						}
					}
					?>
				</div>
				
				<div class='tel location meta'>
					<?php
					$tel		= get_post_meta(get_the_ID(),'tel',true);
					if(!empty($tel)){
						$imageUrl = plugins_url('pictures/tel.png', __DIR__);
						$icon = "<img src='$imageUrl' alt='telephone' loading='lazy' class='location_icon'>";
						echo "<a href='tel:$tel'>$icon Call them  »</a>";
					}
					?>
				</div>
				
				<div class='url location meta'>
					<?php
					$url		= get_post_meta(get_the_ID(),'url',true);
					if(!empty($url)){
						$imageUrl 	= plugins_url('pictures/url.png', __DIR__);
						$icon 		= "<img src='$imageUrl' alt='location' loading='lazy' class='location_icon'>";
						echo "<a href='$url'>$icon Visit website  »</a>";
					}
					?>
				</div>
			</div>
			
			<?php
			
			//only show a map on the item page and if we are logged in
			if(!$archive && is_user_logged_in()){
				//Show a map if one is defined
				$customMapId = get_post_meta(get_the_ID(), 'map_id', true);
				if(is_numeric($customMapId)){
					?>
					<div class='location_map' style='margin-top:15px;margin-bottom:25px;'>
						<h4>Location</h4>
						<?php
						$markers	= get_post_meta(get_the_ID(), 'marker_ids', true);
						$markerId	= '';
						if(is_array($markers) && isset($markers['page_marker'])){
							$markerId	= $markers['page_marker'];
						}
						echo do_shortcode("[ultimate_maps id='$customMapId' map_center='$markerId']");
						?>
					</div>

					<script>
						document.addEventListener('DOMContentLoaded', () => {
							let map	= document.querySelector(".location_map");
							document.querySelector('.widget-area.sidebar').prepend(map);
						});
					</script>
					<?php
				}
			}
			?>
				
			<div class="description location">
				<?php
				//Only show summary on archive pages
				if($archive){
					$excerpt = get_the_excerpt();
					if(empty($excerpt)){
						$url = get_permalink();
						echo "<br><a href='$url'>View description »</a>";
					}else{
						echo $excerpt;
					}
				//Show everything including category specific content
				}else{
					if(empty($post->post_content)){
						echo apply_filters('sim_empty_description', 'No content found...', $post);
					}

					the_content();
				}

				wp_link_pages(
					array(
						'before' => '<div class="page-links">Pages:',
						'after'  => '</div>',
					)
				);
				?>
			</div>
		</div>
	</div>
</article>
