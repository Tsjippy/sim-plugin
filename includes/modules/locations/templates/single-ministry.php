<?php
namespace SIM\LOCATIONS;
use SIM;

/**
 * The Template for displaying all single locations
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if(!isset($skipHeader) || !$skipHeader){
	get_header();
}
?>
	<div id="primary">
		<style>
			@media (min-width: 991px){
				#primary:not(:only-child){
					width: 70%;
				}
			}
		</style>
		<main id="main">
			<?php
			while ( have_posts() ) :
				the_post();
				include(__DIR__.'/content.php');

				if(!empty(get_children(['post_parent' =>get_the_ID()]))){
					$cats['location']	= ['locations'=>[]];

					$categories	= get_the_terms(get_the_ID(), 'locations');
					foreach($categories as $cat){
						$cats['location']['locations'][]	= $cat->slug;
					}

					echo SIM\PAGEGALLERY\pageGallery('Read more', [get_post_type()], 3, $cats, 60);

						// Show relevant media
					$cats		= [];
					foreach($categories as $cat){
						if(count($cats)>1 && $cat->slug == 'ministry'){
							continue;
						}

						$cats[]	= $cat->slug;
					}

					$mediaGallery   = new SIM\MEDIAGALLERY\MediaGallery(['image'], 3, $cats);

					if(isset($_POST['switch-gallery']) && $_POST['switch-gallery'] == 'filter'){
						echo $mediaGallery->filterableMediaGallery();
						$value	= 'gallery';
						$text	= 'View less';
					}else{
						echo $mediaGallery->mediaGallery('Media', 60);
						$value	= 'filter';
						$text	= 'View more...';
					}

					if($mediaGallery->total > 3){
						echo "<form method='post' style='text-align: center;'>";
							echo "<button class='small button' name='switch-gallery' value='$value'>$text</button>";
						echo "</form>";
					}
				}

				// Show any projects linked to this
                projectList();

                // Show the people working here
                echo ministryDescription();
			endwhile;
			
			?> <nav id='post_navigation'>
				<span id='prev'>
					<?php previous_post_link(); ?>
				</span>
				<span id='next' style='float:right;'>
					<?php next_post_link(); ?>
				</span>
			</nav>
			
			<?php
			echo apply_filters('sim-single-template-bottom', '', 'location');
			?>
		</main>
		
		<?php SIM\showComments(); ?>
	</div>

	<?php

	get_sidebar();

	if(!isset($skipFooter) || !$skipFooter){
		get_footer();
	}

 /**
 * Default content for ministry pages
 */
function ministryDescription(){
    $postId     = get_the_ID();
	$html		= "";

	// Show sub ministry gallery
	$ministry	= get_the_title($postId);
	$args		= array(
		'post_parent' => $postId, // The parent id.
		'post_type'   => 'page',
		'post_status' => 'publish',
		'order'       => 'ASC',
	);
	$childPages 		= get_children( $args, ARRAY_A);
	$childPageHtml 	= "";
	if ($childPages){
		$childPageHtml .= "<p><strong>Some of our $ministry are:</strong></p><ul>";
		foreach($childPages as $childPage){
			$childPageHtml .= "<li><a href='{$childPage['guid']}'>{$childPage['post_title']}</a></li>";
		}
		$childPageHtml .= "</ul>";
	}
	
	$latitude 	= get_post_meta($postId,'geo_latitude',true);
	$longitude 	= get_post_meta($postId,'geo_longitude',true);
	if (!empty($latitude) && !empty($longitude)){
		$html .= "<p>";
			$html .= "<a class='button' onclick='Main.getRoute(this,$latitude,$longitude)'>";
				$html .= "Get directions to $ministry";
			$html .= "</a>";
		$html .= "</p>";
	}
	
	if(!empty($childPageHtml)){
		$html = $childPageHtml."<br><br>".$html;
	}

	$html	   .= getLocationEmployees($postId);

	return $html;
}

function projectList(){
    $projects = get_posts([
		'post_type'			=> 'project',
		'posts_per_page'	=> -1,
		'post_status'		=> 'publish',
        'orderby'           => 'title',
        'order'             => 'ASC',
		'meta_query'        => array(
            array(
                'key'	    => 'ministry',
				'value'     => get_the_ID(),
				'compare'   => '='
            )
        )
	]);

    if(empty($projects)){
        return '';
    }

    ?>
    <div class='projects-wrapper'>
        <h4>Projects linked to this ministry are:</h4>
        <ul>
            <?php
            foreach($projects as $project){
                $url    = get_permalink($project->ID);
                echo "<li><a href='$url'>$project->post_title</a></li>";
            }
            ?>
        </ul>
    </div>
    <br>
    <?php
}