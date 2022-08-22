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
		<div class='entry-content<?php if($archive) echo ' archive';?>'>
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

			<div class='location metas' style='margin-top:10px;'>
				<span class='category locationmeta'> 
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
					echo "<img src='$url' alt='category' class='location_icon'>";
					
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
				</span>
				
				<span class='category locationmeta'>
					<?php
					$tel		= get_post_meta(get_the_ID(),'tel',true);
					if(!empty($tel)){
						$imageUrl = plugins_url('pictures/tel.png', __DIR__);
						$icon = "<img src='$imageUrl' alt='telephone' class='location_icon'>";
						echo "<a href='tel:$tel'>$icon Call them  »</a>";
					}
					?>
				</span>
				
				<span class='category locationmeta'>
					<?php
					$url		= get_post_meta(get_the_ID(),'url',true);
					if(!empty($url) && $url != 'https://www.'){
						$imageUrl 	= plugins_url('pictures/url.png', __DIR__);
						$icon 		= "<img src='$imageUrl' alt='location' class='location_icon'>";
						echo "<a href='$url'>$icon Visit website  »</a>";
					}
					?>
				</span>
			</div>
			
			<?php
			
			//only show a map on the item page and if we are logged in
			if(!$archive && is_user_logged_in()){
				//Show a map if one is defined
				$customMapId = get_post_meta(get_the_ID(),'map_id',true);
				if(is_numeric($customMapId)){
				?>
				<div class='location_map' style='margin-top:15px;margin-bottom:25px;'>
					<?php
					echo do_shortcode("[ultimate_maps id='$customMapId']");
					?>
				</div>
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
				
					$cats = get_the_terms(get_the_ID(),'locations');
					foreach($cats as $cat){						
						if($cat->slug == 'ministry'){
							echo ministryDescription(get_the_ID());
						}
					}
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
