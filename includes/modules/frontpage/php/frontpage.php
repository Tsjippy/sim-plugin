<?php
namespace SIM\FRONTPAGE;
use SIM;

//generate_before_header
// diplay buttons
$hook_name	= SIM\get_module_option('frontpage','header_hook');
if(!empty($hook_name)){
	//Add a button to the header
	add_action($hook_name, function(){
		if (is_user_logged_in() and (is_page(SIM\get_module_option('login','home_page')) or is_front_page())){
			$button1	= SIM\get_module_option('frontpage', 'first_button');
			$button2	= SIM\get_module_option('frontpage', 'second_button');

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
$hook_name	= SIM\get_module_option('frontpage','after_main_content_hook');
if(!empty($hook_name)){
	add_action($hook_name, function(){
		//if on home page and prayer module activated
		if(is_page(SIM\get_module_option('login','home_page')) and SIM\get_module_option('prayer','enable')){
			
			if (is_user_logged_in() and function_exists('SIM\PRAYER\prayer_request')){
				$prayerrequest = SIM\PRAYER\prayer_request();
				if (empty($prayerrequest)) return;
			
				echo "<article>";
					echo "<div name='prayer_request' style='text-align: center; margin-left: auto; margin-right: auto; font-size: 18px; color:#999999; width:80%; max-width:800px;'>";
						echo "<h3 id='prayertitle'>The prayer request of today:</h3>";
						echo "<p>$prayerrequest</p>";
					echo "</div>";
					if(SIM\get_module_option('events', 'enable')){
						echo SIM\EVENTS\birthday();
					}
				echo '</article>';
			}
		}
	});
}

//Show the latest news
//generate_before_footer
$hook_name	= SIM\get_module_option('frontpage','before_footer_hook');
if(!empty($hook_name)){
	add_action($hook_name, function() {
		//if on home page
		if(is_page(SIM\get_module_option('login','home_page')) or is_front_page()){
			// If not logged in and on the logged in homepage
			if (!is_user_logged_in() and is_page(SIM\get_module_option('login','home_page'))){
				return;
			}

			//Show the ministry gallery
			page_gallery();
			$args                   = array('ignore_sticky_posts' => true,);
			$args['post_type'] 		= SIM\get_module_option('frontpage', 'news_post_types');
			$args['post_status'] 	= 'publish';

			//Only include posts who are published less than $max_news_age ago
			$max_news_age	= SIM\get_module_option('frontpage', 'max_news_age');
			$args['date_query']		= array(
				array(
					'after' => array(
						'year' => date('Y',strtotime("-$max_news_age")),
						'month' => date('m',strtotime("-$max_news_age")),
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
					'key'		=> 'missionary_id',
					'compare'	=> 'NOT EXISTS'
				),
			);
			
			//If not logged in..
			if ( !is_user_logged_in() ) {
				//Only get news wih the public category
				$blog_categories = [get_cat_ID('Public')];
				$args['tax_query'] = array(
					array(
						'taxonomy' => 'category',
						'field'    => 'term_id',
						'terms'    => $blog_categories,
					),
				);
				
				//Do not show password protected news
				$args['has_password'] = false;
			}else{
				$user = wp_get_current_user();

				//exclude birthdays and anniversaries
				$args['tax_query'] = array(
					array(
						'taxonomy' => 'eventtype',
						'field'    => 'term_id',
						'terms'    => [
							get_term_by('slug', 'anniversary','eventtype')->term_id,
							get_term_by('slug', 'birthday','eventtype')->term_id
						],
						'operator' => 'NOT IN'
					),
				);

				//Hide confidential items
				if(in_array('nigerianstaff',$user->roles)){
					$args['tax_query'][] = 
						array(
							'taxonomy' => 'eventtype',
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
		
			$allowed_html = array(
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
									<?php echo wp_kses( force_balance_tags( get_the_title() ), $allowed_html ); ?>
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

//Function to show a gallery of 3 ministries
function page_gallery(){
	?>
	<article id="page-gallery">
		<h3 id="page-gallery-title">See what we do:</h3>
		<div class="row">
		<?php
		for ($x = 1; $x <= 3; $x++) {
			?>
			<div class="page-gallery">
				<div class="card card-profile card-plain">
					<div class="col-md-5">
						<div class="card-image">
							<?php
							$pageId		= SIM\get_module_option('frontpage', "page$x");
							$pictureUrl	= get_the_post_thumbnail_url($pageId);
							$pageUrl	= get_permalink($pageId);
							$title		= SIM\get_module_option('frontpage', "title$x");
							if(!$title) $title	= get_the_title($pageId);
							$text		= SIM\get_module_option('frontpage', "description$x");
							if(!$text) $text	= get_the_excerpt($pageId);

							echo "<a href='$pageUrl'>";
								echo "<img class='img' src='$pictureUrl' alt='' title='$title'>";
							?>
							</a>
						</div>
					</div>
					<div class="col-md-7">
						<div class="content">
							<?php
							echo "<a href='$pageUrl'>";
								echo "<h4 class='card-title'>$title</h4>";
								echo "<p class='card-description'>$text</p>";
							?>
							</a>
						</div>
					</div>
				</div>
			</div>
			<?php
		}
		?>
		</div>
	</article>
	<?php
}

//Add the home class
add_filter( 'body_class',function ( $classes ) {
	if(is_page(SIM\get_module_option('login','home_page')) or is_front_page()){
		$classes[] = 'home';
	}
    return $classes;
});