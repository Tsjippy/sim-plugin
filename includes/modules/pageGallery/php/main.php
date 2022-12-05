<?php
namespace SIM\PAGEGALLERY;
use SIM;

/**
 * Function to show a gallery of 3 ministries
 * They will only be listed if they have a featured image!
 *
 * @param	array	$postTypes		Array of posttypes to include or an array of fixed post ids
 * @param	int		$amount			The amount of pages to show
 * @param	array	$categories		The categories of this page type to include. Should be an multidimensional array of taxonmies indexed by postypes containing categories
 * @param	int		$speed			The speed the pages should change
 *
 * @return	string					The html
 */
function pageGallery($title, $postTypes=[], $amount=3, $categories = [], $speed = 60){
	wp_enqueue_script('sim_page_gallery_script');

	ob_start();

	$posts	= [];

	if(empty($postTypes) || !is_array($postTypes)){
		return '';
	}elseif(is_numeric($postTypes[0])){
		foreach($postTypes as $postId){
			$posts[]	= get_post($postId);
		}
	}else{
		foreach($postTypes as $type){
			$args = array(
				'post_type'			=> $type,
				'orderby'			=> 'rand',
				'posts_per_page' 	=> $amount,
				'meta_query' 		=> array(
					array(
						'key' 		=> '_thumbnail_id',
						'compare' 	=> 'EXISTS'
					),
				)
			);

			// if we should only include a few categories
			if(!empty($categories[$type])){
				$args['tax_query']  = ['relation' => 'OR'];

				foreach($categories[$type] as $tax => $cats){
					foreach($cats as $cat){
						$taxQuery	= [
							'taxonomy' 			=> $tax,
							'terms' 			=> $cat
						];

						if(!is_numeric($cat)){
							$taxQuery['field']	= 'slug';
						}

						$args['tax_query'][]	= $taxQuery;
					}
				}
			}

			// Only show public if not logged in
			if(!is_user_logged_in()){
				$isPublicQuery	= ['relation' => 'OR'];
				foreach(get_object_taxonomies($type) as $tax){
					$isPublicQuery[]	= [
						'taxonomy' => $tax,
						'field'    => 'slug',
						'terms'    => array( 'public' ),
					];
				}
				
				$args['tax_query']	= [
					'relation' => 'AND',
					$isPublicQuery,
					$args['tax_query']
				];
			}

			$args	= apply_filters('sim-frontpage-post-gallery-posts', $args, $postTypes);

			$posts	= array_merge($posts, get_posts($args));
		}
	}

	if(empty($posts)){
		?>
		<article class="page-gallery-article">
			<h3 class="page-gallery-title"><?php echo $title;?></h3>
			<p>No pages found...</p>
		</article>
		<?php
		return ob_get_clean();
	}

	// make sure we only try to display as many pages as available
	$amount	= min(count($posts), $amount);

	$list	= $postTypes;
	?>
	<article class="page-gallery-article" data-posttypes='<?php echo json_encode($postTypes);?>' data-categories='<?php echo json_encode($categories);?>' data-speed='<?php echo $speed;?>'>
		<h3 class="page-gallery-title"><?php echo $title;?></h3>
		<div class="row">
		<?php
		while($amount > 0) {
			// find the first post of this type
			foreach($posts as $index=>$post){
				if($post->post_type == $list[0]){
					// remove this type from the working list, so we take another posttype the next time
					unset($list[0]);
					if(empty($list)){
						$list	= $postTypes;
					}else{
						$list	= array_values($list);
					}

					// Remove the post from the post list so we do not use it again
					unset($posts[$index]);
					break;
				}
			}
			$pageId		= $post->ID;
			$pictureUrl	= get_the_post_thumbnail_url($pageId);
			$pageUrl	= get_permalink($pageId);
			$title		= $post->post_title;
			?>
			<div class="page-gallery">
				<div class="card card-profile card-plain">
					<div class="col-md-5">
						<div class="card-image">
							<a href='<?php echo $pageUrl;?>'>
								<img class='img' src='<?php echo $pictureUrl;?>' alt='' title='<?php echo $title;?>' loading='lazy'>
							</a>
						</div>
					</div>
					<div class="col-md-7">
						<div class="content">
							<a href='<?php echo $pageUrl;?>'>
								<h4 class='card-title'><?php echo $title;?></h4>
								<p class='card-description'><?php echo get_the_excerpt($pageId);?></p>
							</a>
						</div>
					</div>
				</div>
			</div>
			<?php
			$amount--;
		}
		?>
		</div>
	</article>
	<?php
	return ob_get_clean();
}