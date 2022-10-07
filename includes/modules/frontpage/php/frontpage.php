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

			if(SIM\getModuleOption(MODULE_SLUG,'galery-type') == 'dynamic'){
				$postTypes	= SIM\getModuleOption(MODULE_SLUG, "post_types");
				$categories	= SIM\getModuleOption(MODULE_SLUG, "categories");
	
				echo SIM\PAGEGALLERY\pageGallery('See what we do:', $postTypes, 3, $categories, SIM\getModuleOption(MODULE_SLUG, "speed"));
			}elseif(SIM\getModuleOption(MODULE_SLUG,'galery-type') == 'static'){
				//Show the ministry gallery
				$pageIds	= [
					SIM\getModuleOption(MODULE_SLUG, "title1"),
					SIM\getModuleOption(MODULE_SLUG, "title2"),
					SIM\getModuleOption(MODULE_SLUG, "title3"),
				];

				echo SIM\PAGEGALLERY\pageGallery('See what we do:', $pageIds);
			}
			
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

//Add the home class
add_filter( 'body_class',function ( $classes ) {
	if(in_array(get_the_ID(), SIM\getModuleOption(MODULE_SLUG,'home_page')) || is_front_page()){
		$classes[] = 'home';
	}
    return $classes;
});