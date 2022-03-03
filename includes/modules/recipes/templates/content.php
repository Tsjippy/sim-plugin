<?php
namespace SIM\RECIPES;
use SIM;

/**
 * The content of a recipe shared between a single post, archive or the recipes page.
 *
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Load js
wp_enqueue_script('sim_plurarize_script',plugins_url('js/recipe.min.js', __DIR__), array(), ModuleVersion,true);
?>
<article id="post-<?php the_ID(); ?>" <?php post_class(); ?> <?php generate_do_microdata( 'article' ); ?>>
	<div class="cat_card<?php if(!$archive) echo ' inside-article';?>">
		
		<?php 
		if($archive){
			$url = get_permalink(get_the_ID());
			echo the_title( "<h3 class='archivetitle'><a href='$url'>", '</a></h3>' );
		}else{
			do_action( 'sim_before_content');
		}
		?>
		<div class='entry-content<?php if($archive) echo ' archive';?>'>
			<div class='picture'>
				<?php
				the_post_thumbnail([250,200]);
				?>
			</div>
			
			<div class='author'>
				Shared by: <a href='<?php echo SIM\get_user_page_url(get_the_author_meta('ID')) ?>'><?php the_author(); ?></a>
			</div>
			
			<div class='recipe metas'>
				<?php 
				//Do not show the category on a category page
				if(!$archive){
				?>
				<span class='category recipemeta'> 
					<?php
					$categories = wp_get_post_terms(
						get_the_ID(), 
						'recipetype',
						array(
							'orderby'   => 'name',
							'order'     => 'ASC',
							'fields'    => 'id=>name',
							'childless'	=> true,//only show categories without children
						) 
					);
					
					$url = plugins_url().'/sim-plugin/includes/pictures/recipe_category.png';
					echo "<img src='$url' alt='category' class='recipe_icon'>";
					
					//print_array($categories);
					$i = 1;
					foreach($categories as $id=>$category){
						if($i != 1) echo ', ';
						$url = get_term_link($id);
						$category = strtolower($category);
						echo "<a href='$url'>$category</a>";
						$i++;
					}
					?>
				</span>
				<?php 
				} 
				?>
				<span class='cooking_time recipemeta'>
					<?php 
					$url = plugins_url().'/sim-plugin/includes/pictures/recipe_coocking_time.png';
					echo "<img src='$url' alt='category' class='recipe_icon'>";
					echo get_post_meta(get_the_ID(),'time_needed',true); 
					if(!$archive) echo 'minutes';
					?>
				</span>
				<span class='serves recipemeta'>
					<?php
					$url = plugins_url().'/sim-plugin/includes/pictures/recipe_serves.png';
					echo "<img src='$url' alt='category' class='recipe_icon'>";
					$persons = get_post_meta(get_the_ID(),'serves',true);
					echo "<select class='serves_select' data-originalvalue='$persons' style='padding:0px;'>";
					for($i = 1; $i<=10; $i++) {
						echo "<option value='$i'";
						if($i == $persons) echo " selected";
						echo ">$i</option>"; 
					}
					echo "</select>";

					if(!$archive){
						if($persons == 1){
							echo  ' <span class="personspan">person</span>';
						}else{
							echo  ' <span class="personspan">people</span>';
						}
					}
					?>
				</span>
			</div>
			
			<div class='ingredients recipe'>
				Ingredients:
				<ul class='ingredients'>
				<?php
				$ingredients = explode("\n", trim(get_post_meta(get_the_ID(),'ingredients',true)));
				//print_array($ingredients);
				
				foreach($ingredients as $key=> $ingredient){
					if($key == 4 and $archive){
						echo "<li>...</li>";
						break;
					}else{
						echo "<li>$ingredient</li>";
					}
				}
				?>
				</ul>
			</div>
			
			<div class="instructions recipe">
				<strong>This is how you should make the 
				<?php
				echo strtolower(get_the_title());
				echo ':</strong><br>';
				if($archive){
					the_excerpt();
				}else{
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
