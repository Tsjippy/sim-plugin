<?php
namespace SIM\FRONTPAGE;
use SIM;

//generate_before_header
$hook_name	= SIM\get_module_option('frontpage','header_hook');
if(!empty($hook_name)){
	//Add a button to the header
	add_action($hook_name, function(){
		if (is_user_logged_in() and (is_page(SIM\get_module_option('login','home_page')) or is_front_page())){
			$base_url			= get_site_url();
			$first_btn_text		= SIM\get_module_option('template_specific', 'first_button_text');
			$first_btn_link		= SIM\get_module_option('template_specific', 'first_button_url');
			if(strpos($first_btn_link, $base_url) === false){
				$first_btn_link	= $base_url.$first_btn_link;
			}
			$second_btn_text	= SIM\get_module_option('template_specific', 'second_button_text');
			$second_btn_link	= SIM\get_module_option('template_specific', 'second_button_url');
			if(strpos($second_btn_link, $base_url) === false){
				$second_btn_link	= $base_url.$second_btn_link;
			}

			$html = '<div id="header_buttons">';
				$html .= "<a id='first_button' href='$first_btn_link' title='$first_btn_text' class='btn btn-primary header_button' id='header_button1'>$first_btn_text</a>";
				$html .= "<a id='first_button' href='$second_btn_link' title='$second_btn_text' class='btn btn-primary header_button' id='header_button1'>$second_btn_text</a>";
			$html .= '</div>';
			echo $html;
		}
	});
}

//generate_after_main_content
$hook_name	= SIM\get_module_option('frontpage','after_main_content_hook');
if(!empty($hook_name)){
	add_action($hook_name, function(){
		//if on home page
		if(is_page(SIM\get_module_option('login','home_page'))){
			
			if (is_user_logged_in()){
				$prayerrequest = SIM\prayer_request();
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
//generate_before_footer
if(!empty($hook_name)){
	add_action($hook_name, function() {
		global $PublicCategoryID;
		global $ConfCategoryID;
		
		//if on home page
		if(is_page(SIM\get_module_option('login','home_page')) or is_front_page()){
			//Show the ministry gallery
			ministry_gallery();
			$args                   = array('ignore_sticky_posts' => true,);
			$args['post_type'] 		= array('post', 'event','pages','recipe','location');
			$args['post_status'] 	= 'publish';

			//Only include posts who are published less than 2 months ago
			$args['date_query']		= array(
				array(
					'after' => array(
						'year' => date('Y',strtotime("-2 months")),
						'month' => date('m',strtotime("-2 months")),
						'day' => date('d'),
					)
				)
			);
			
			//exclude private events
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
			);
			
			//If not logged in..
			if ( !is_user_logged_in() ) {
				//Only get news wih the public category
				$blog_categories = [$PublicCategoryID];
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
							'terms'    => [$ConfCategoryID],
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
				'i'      => array(
					'class' => array(),
				),
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
			echo '<div id="newslinkdiv"><p><a name="newslink" id="newslink" href="'.get_site_url().'/news/">Read all news items →</a></p></div></div>';
			echo '</article>';

			wp_reset_postdata();
		}
	});
}

//Function to show a gallery of 3 ministries
function ministry_gallery(){
	$ministry_adds = [];
	$ministry_adds['image_ids'] = [get_theme_mod( 'sim_ministry_image_1', '' ),get_theme_mod( 'sim_ministry_image_2', '' ),get_theme_mod( 'sim_ministry_image_3', '' )];
	$ministry_adds['links'] = [get_theme_mod( 'sim_ministry_link_1', '' ),get_theme_mod( 'sim_ministry_link_2', '' ),get_theme_mod( 'sim_ministry_link_3', '' )];
	$ministry_adds['titles'] = [get_theme_mod( 'sim_ministry_title_1', '' ),get_theme_mod( 'sim_ministry_title_2', '' ),get_theme_mod( 'sim_ministry_title_3', '' )];
	$ministry_adds['texts'] = [get_theme_mod( 'sim_ministry_text_1', '' ),get_theme_mod( 'sim_ministry_text_2', '' ),get_theme_mod( 'sim_ministry_text_3', '' )];
	
	?>
	<article id="ministry-gallery">
		<h3 id="ministries-gallery-title">See what we do:</h3>
		<div class="row">
		<?php
		for ($x = 0; $x <= 2; $x++) {
			?>
			<div class="ministry-add">
				<div class="card card-profile card-plain">
					<div class="col-md-5">
						<div class="card-image">
							<?php
							echo '<a href="'.get_site_url().$ministry_adds['links'][$x].'">';
								$image_url = wp_get_attachment_url($ministry_adds['image_ids'][$x]);
								echo '<img class="img" src="'.$image_url.'" alt="'.$ministry_adds['titles'][$x].'" title="'.$ministry_adds['titles'][$x].'">';
							?>
							</a>
						</div>
					</div>
					<div class="col-md-7">
						<div class="content">
							<?php
							echo '<a href="'.get_site_url().$ministry_adds['links'][$x].'">';
								echo '<h4 class="card-title">'.$ministry_adds['titles'][$x].'</h4>';
								echo '<p class="card-description">'.$ministry_adds['texts'][$x].'</p>';
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