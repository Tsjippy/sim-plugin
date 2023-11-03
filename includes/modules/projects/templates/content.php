<?php
namespace SIM\PROJECTS;
use SIM;

/**
 * The content of a project shared between a single post, archive or the recipes page.
**/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$archive	= false;
if(is_tax() || is_archive()){
	$archive	= true;
}

$class	= '';
if(!$archive){
	$class	= '';
}
?>
<style>
	.metas{
		margin-top:10px;
		display: flex;
		flex-wrap: wrap;
	}

	.project.meta{
		margin-right: 10px;
	}

	.cat_card{
		padding: 10px;
	}
</style>
<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
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

			<div class='project metas'>
				<div class='category project meta'>
					<?php
					$categories = wp_get_post_terms(
						get_the_ID(),
						'projects',
						array(
							'orderby'   => 'name',
							'order'     => 'ASC',
							'fields'    => 'id=>name'
						)
					);
					
					//First loop over the cat to see if any parent cat needs to be removed
					foreach($categories as $id=>$category){
						//Get the child categories of this category
						$children = get_term_children($id, 'projects');
						
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
				
				<div class='number project meta'>
					<?php
					$url	= plugins_url('pictures/project.png', __DIR__);
					echo "<img src='$url' alt='category' loading='lazy' class='project_icon'>";
					echo get_post_meta(get_the_ID(), 'number', true);
				echo "</div>";

				$ministry = get_post_meta(get_the_ID(), 'ministry', true);

				if(!empty($ministry)){
					echo "<div class='ministry project meta'>";
						$imageUrl = plugins_url('pictures/ministry.png', __DIR__);
						$icon 	= "<img src='$imageUrl' alt='email' loading='lazy' class='project_icon'>";
						$url	= get_permalink($ministry);
						$title	= get_the_title($ministry);
						echo "<a href='$url'>$icon $title</a><br>";
					echo "</div>";
				}

				$manager		= get_post_meta(get_the_ID(), 'manager', true);

				if(!is_array($manager)){
					$manager	= json_decode($manager, true);
				}
				
				echo "<div class='number project meta'>";
					$imageUrl = plugins_url('pictures/manager.png', __DIR__);
					$icon = "<img src='$imageUrl' alt='manager' loading='lazy' class='project_icon'>";
					if(!empty($manager['userid'])){
						$userPageUrl		= SIM\maybeGetUserPageUrl($manager['userid']);
						echo "<a href='$userPageUrl'>$icon {$manager['name']}</a>";
					}else{
						echo $icon.$manager['name'];
					}
				echo "</div>";

				if(!empty($manager['tel'])){
					echo "<div class='tel project meta'>";
						$imageUrl = plugins_url('pictures/tel.png', __DIR__);
						$icon = "<img src='$imageUrl' alt='telephone' loading='lazy' class='project_icon'>";
						echo "<a href='tel:{$manager['tel']}'>$icon {$manager['tel']}</a>";
					echo "</div>";
				}

				if(!empty($manager['email'])){
					echo "<div class='email project meta'>";
						$imageUrl = plugins_url('pictures/email.png', __DIR__);
						$icon = "<img src='$imageUrl' alt='email' loading='lazy' class='project_icon'>";
						echo "<a href='mailto:{$manager['email']}'>$icon {$manager['email']}</a>";
					echo "</div>";
				}
				?>
				
				<div class='url project meta'>
					<?php
					$url		= get_post_meta(get_the_ID(),'url',true);
					if(!empty($url)){
						$imageUrl 	= plugins_url('pictures/url.png', __DIR__);
						$icon 		= "<img src='$imageUrl' alt='project' loading='lazy' class='project_icon'>";
						echo "<a href='$url'>$icon Visit website  »</a>";
					}
					?>
				</div>
			</div>
				
			<div class="description project">
				<?php
				//Only show summary on archive pages
				if($archive){
					$excerpt =  force_balance_tags(wp_kses_post(get_the_excerpt()));
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
