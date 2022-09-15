<?php
namespace SIM\FRONTPAGE;
use SIM;

//generate_before_header
// diplay buttons
$hookName	= SIM\getModuleOption(MODULE_SLUG, 'header_hook');
if(!empty($hookName)){
	//Add a button to the header
	add_action($hookName, function(){
		if (is_user_logged_in() && (in_array(get_the_ID(), SIM\getModuleOption(MODULE_SLUG,'home_page')) || is_front_page())){
			$button1	= SIM\getModuleOption(MODULE_SLUG, 'first_button');
			$button2	= SIM\getModuleOption(MODULE_SLUG, 'second_button');

			$text1		= get_the_title($button1);
			$text2		= get_the_title($button2);

			$url1		= get_the_permalink($button1);
			$url2		= get_the_permalink($button2);

			$html = '<div id="header_buttons">';
				$html .= "<a id='first_button' href='$url1' title='$text1' class='btn btn-primary header_button' id='header_button1'>$text1</a>";
				$html .= "<a id='second_button' href='$url2' title='$text2' class='btn btn-right header_button' id='header_button1'>$text2</a>";
			$html .= '</div>';
			echo $html;
		}
	});
}

//generate_after_main_content
//display prayer message and birtdays
$hookName	= SIM\getModuleOption(MODULE_SLUG, 'after_main_content_hook');
if(!empty($hookName)){
	add_action($hookName, function(){
		//if on home page and prayer module activated
		if(in_array(get_the_ID(), SIM\getModuleOption(MODULE_SLUG,'home_page')) && is_user_logged_in()){
			$message	= apply_filters('sim_frontpage_message', '');
				
			echo "<article>";
				echo $message;
			echo '</article>';
		}
	});
}

//Show the latest news
//generate_before_footer
$hookName	= SIM\getModuleOption(MODULE_SLUG, 'before_footer_hook');
if(!empty($hookName)){
	add_action($hookName, function() {
		//if on home page
		if(in_array(get_the_ID(), SIM\getModuleOption(MODULE_SLUG,'home_page')) || is_front_page()){
			// If not logged in and on the logged in homepage
			if (!is_user_logged_in() && in_array(get_the_ID(), SIM\getModuleOption(MODULE_SLUG,'home_page'))){
				return;
			}

			//Show the ministry gallery
			echo pageGallery('See what we do:');
			$args                   = array('ignore_sticky_posts' => true,);
			$args['post_type'] 		= SIM\getModuleOption(MODULE_SLUG, 'news_post_types');
			$args['post_status'] 	= 'publish';

			//Only include posts who are published less than $max_news_age ago
			$maxNewsAge	= SIM\getModuleOption(MODULE_SLUG, 'max_news_age');
			$args['date_query']		= array(
				array(
					'after' => array(
						'year' => date('Y',strtotime("-$maxNewsAge")),
						'month' => date('m',strtotime("-$maxNewsAge")),
						'day' => date('d'),
					)
				)
			);
			
			//exclude private events and user pages
			$args['meta_query']		= array(
				'relation' => 'AND',
				array(
					'key' => 'onlyfor',
					'compare' => 'NOT EXISTS',
				), 
				array(
					'relation' => 'OR',
					array(
						'key' => 'expirydate'
					),
					array(
						'key' => 'expirydate',
						'value' => date('Y-m-d'),
						'compare' => '>',
					),
					array(
						'key' => 'expirydate',
						'compare' => 'NOT EXISTS',
					)
				),
				array(
					'key'		=> 'user_id',
					'compare'	=> 'NOT EXISTS'
				),
			);
			
			//If not logged in..
			if ( !is_user_logged_in() ) {
				//Only get news wih the public category
				$blogCategories = [get_cat_ID('Public')];
				$args['tax_query'] = array(
					array(
						'taxonomy' => 'category',
						'field'    => 'term_id',
						'terms'    => $blogCategories,
					),
				);
				
				//Do not show password protected news
				$args['has_password'] = false;
			}else{
				$user = wp_get_current_user();

				//exclude birthdays and anniversaries
				$args['tax_query'] = array(
					array(
						'taxonomy' => 'events',
						'field'    => 'term_id',
						'terms'    => [
							get_term_by('slug', 'anniversary','events')->term_id,
							get_term_by('slug', 'birthday','events')->term_id
						],
						'operator' => 'NOT IN'
					),
				);

				//Hide confidential items
				$confidentialGroups	= (array)SIM\getModuleOption('contentfilter', 'confidential-roles');
				
				if(array_intersect($confidentialGroups, $user->roles)){
					$args['tax_query'][] = 
						array(
							'taxonomy' => 'events',
							'field'    => 'term_id',
							'terms'    => [get_cat_ID('Confidential')],
							'operator' => 'NOT IN'
						);
				}
			}
			
			//Get all the posts using the previously defined arguments
			$loop = new \WP_Query( $args );
			?>
			<article id="news">
				<div id="rowwrap">
					<h2 id="news-title">Latest news</h2>
					<div class="row">
			<?php

			if ( ! $loop->have_posts() ) {
				//Show message if there is no news
				?>
							<article class="news-article">
								<div class="card card-plain card-blog">
									<div class="content">
										<h4 class="card-title entry-title">
											<p>There is currently no news</p>
										</h4>
									</div>
								</div>
							</article>
						</div>
						<div id="newslinkdiv"></div>
					</div>
				</article>
				<?php
				return;
			}
		
			$allowedHtml = array(
				'br'     => array(),
				'em'     => array(),
				'strong' => array(),
				'i'      => array(),
				'class' => array(),
				'span'   => array(),
			);
			
			$i = 1;
			while ( $loop->have_posts() ){
				$loop->the_post();
				
				?>
				<article class="news-article">
					<div class="card card-plain card-blog">
						<?php if ( has_post_thumbnail() ) : 
							echo '<div class="card-image">';
								echo '<a href="'.get_permalink().'" style="background-image: url('.get_the_post_thumbnail_url().');"></a>';
							echo '</div>';
						endif; ?>
						<div class="content">
							<h4 class="card-title entry-title">
								<a class="blog-item-title-link" href="<?php echo esc_url( get_permalink() ); ?>" title="<?php the_title_attribute(); ?>" rel="bookmark">
									<?php echo wp_kses( force_balance_tags( get_the_title() ), $allowedHtml ); ?>
								</a>
							</h4>
							<p class="card-description"><?php echo force_balance_tags(wp_kses_post( get_the_excerpt())); ?></p>
						</div>
					</div>
				</article>
				<?php
				$i++;
			}
			echo '</div>';
			echo '<div id="newslinkdiv"><p><a name="newslink" id="newslink" href="'.SITEURL.'/news/">Read all news items →</a></p></div></div>';
			echo '</article>';

			wp_reset_postdata();
		}
	});
}

/**
 * Function to show a gallery of 3 ministries
 * 
 * @param	array	$postTypes		Array of posttypes to include or an array of fixed post ids
 * @param	int		$amount			The amount of pages to show
 * @param	array	$categories		The categories of this page type to include
 * @param	int		$speed			The speed the pages should change
 * 
 * @return	string					The html
 */
function pageGallery($title, $postTypes=[], $amount=3, $categories = [], $speed = 60){
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

			if(!empty($categories[$type])){
				$args['tax_query'] = ['relation' => 'OR'];

				foreach($categories[$type] as $tax => $cats){
					$args['tax_query'][]	= [
						'taxonomy' 			=> $tax,
						'field' 			=> 'slug',
						'terms' 			=> $cats,
						'include_children' 	=> false // Remove if you need posts from term 7 child terms
					];
				}
			}

			$posts	= array_merge($posts, get_posts($args));
		}
	}

	if(empty($posts)){
		?>
		<article id="page-gallery">
			<h3 id="page-gallery-title"><?php echo $title;?></h3>
			<p>No pages found...</p>
		</article>
		<?php
		return ob_get_clean();
	}

	// make sure we only try to display as many pages as available
	$amount	= min(count($posts), $amount);

	$list	= $postTypes;
	?>
	<article id="page-gallery">
		<h3 id="page-gallery-title"><?php echo $title;?></h3>
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

//Add the home class
add_filter( 'body_class',function ( $classes ) {
	if(in_array(get_the_ID(), SIM\getModuleOption(MODULE_SLUG,'home_page')) || is_front_page()){
		$classes[] = 'home';
	}
    return $classes;
});