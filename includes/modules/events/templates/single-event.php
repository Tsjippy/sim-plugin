<?php
namespace SIM\EVENTS;
use SIM;
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

wp_enqueue_style('sim_events_css');

get_header(); ?>
	<div id="primary">
		<main>
			<?php
			while ( have_posts() ) :

				the_post();

				event_contents();

			endwhile;
			?>
		</main>
		<?php SIM\show_comments(); ?>
	</div>

<?php
//get_sidebar();

get_footer();

function event_contents(){
	$events		= new Events();
	$event		= $events->retrieve_single_event(get_the_ID());
	$date		= $events->get_date($event);
	$time		= $events->get_time($event);
	$meta		= get_post_meta($event->ID,'eventdetails',true);

	?>
	<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
		<div class="cat_card inside-article">
			<?php do_action( 'sim_before_content' );?>
			<div class='entry-content'><?php
				
				$categories = wp_get_post_terms(
					get_the_ID(), 
					'eventtype',
					array(
						'orderby'   => 'name',
						'order'     => 'ASC',
						'fields'    => 'id=>name'
					) 
				);
				if(is_user_logged_in()){
					?>
					<div class="description event">
						<?php 
						if(!empty($categories)){
						?>
							<span class='category eventmeta'> 
								<?php
								echo "<img src='".PICTURESURL."/event_category.png' alt='category' class='event_icon'>";
								
								//First loop over the cat to see if any parent cat needs to be removed
								foreach($categories as $id=>$category){
									//Get the child categories of this category
									$children = get_term_children($id,'eventtype');
									
									//Loop over the children to see if one of them is also in he cat array
									foreach($children as $child){
										if(isset($categories[$child])){
											unset($categories[$id]);
											break;
										}
									}
								}
								
								//now loop over the array to print the categories
								$last_key	 = array_key_last($categories);
								foreach($categories as $id=>$category){
									//Only show the category if all of its subcats are not there
									$url = get_term_link($id);
									$category = ucfirst($category);
									echo "<a href='$url'>$category</a>";
									
									if($id != $last_key) echo ', ';
								}
								?>
							</span>
						<?php
						}

						the_content();

						wp_link_pages(
							array(
								'before' => '<div class="page-links">Pages:',
								'after'  => '</div>',
							)
						);
						?>
					</div>

					<div class='event metas' style='margin-top:10px;'>
						<div class="event-meta">
							<div class="single-event-date">
								<?php
								echo "<img src='".PICTURESURL."/date.png' alt='date' class='event_icon'>";
								?>
								<h4>DATE</h4>
								<dl>
									<dd>
										<?php
										echo $date;
										?>
									</dd>
								</dl>
							</div>
							<div class="event-time">
								<?php
								echo "<img src='".PICTURESURL."/time_red.png' alt='time' class='event_icon'>";
								?>
								<h4 class="time">TIME</h4>
								<dl>
									<dd>
										<?php
										echo $time;
										?>
									</dd>
								</dl>
							</div>

							<?php 
							if(!empty($meta['repeat']['type'])){
							?>
								<div class="event-repeat">
									<?php
									echo "<img src='".PICTURESURL."/repeat_small.png' alt='repeat' class='event_icon'>";
									?>
									<h4 class="repeat">REPEATS</h4>
									<dl>
										<dd>
											<?php
											echo ucfirst($meta['repeat']['type']);
											if(!empty($meta['repeat']['enddate'])){
												echo " until ".date('j F Y',strtotime($meta['repeat']['enddate']));
											}
											if(!empty($meta['repeat']['amount'])){
												$repeat_amount = $meta['repeat']['amount'];
												if($repeat_amount != 90){
													echo " for $repeat_amount times";
												}
											}
											?>
										</dd>
									</dl>
								</div>
							<?php 
							} 
							if(!empty($event->location)){
							?>
								<div class="event-location">
									<?php
									echo "<img src='".PICTURESURL."/location_red.png' alt='' class='event_icon'>";
									?>
									<h4>LOCATION</h4>
									<div class='location_details'>
										<?php
										echo $events->get_location_detail($event);
										?>
									</div>
								</div>
							<?php
							}
							if(!empty($event->organizer)){
							?>
								<div class="event-organizer">
									<?php
									echo "<img src='".PICTURESURL."/organizer.png' alt='' class='event_icon'>";
									?>
									<h4>ORGANIZER</h4>
									<div class='author_details'>
										<?php
										echo $events->get_author_detail($event);
										?>
									</div>
								</div>
							<?php
							}
							echo $events->event_export_html($event);	
							?>
						</div>
					</div>
					<?php
				}
				?>
			</div>
		</div>
	</article>

	<?php
}
//get_footer();