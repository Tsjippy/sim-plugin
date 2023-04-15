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

				showMedia();

				// Show any projects linked to this
                projectList();

                // Show the people working here
                echo ministryDescription();

				showRelevantPages();
			endwhile;
			
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

function showMedia(){
	// Show relevant media
	$gradient		= SIM\getModuleOption(MODULE_SLUG, 'gallery-background-color-gradient');

	$cats			= [];
	$categories		= get_the_terms(get_the_ID(), 'locations');
	foreach($categories as $cat){
		if(count($categories) > 1 && $cat->slug == 'ministry'){
			continue;
		}

		$cats[]	= $cat->slug;
	}

	$color			= SIM\getModuleOption(MODULE_SLUG, 'media-gallery-background-color');
	$mediaGallery   = new SIM\MEDIAGALLERY\MediaGallery(['image'], 6, $cats, true, 1, '', $color, $gradient);

	if(isset($_POST['switch-gallery']) && $_POST['switch-gallery'] == 'filter'){
		echo $mediaGallery->filterableMediaGallery();
		$value	= 'gallery';
		$text	= 'View less';
	}else{
		echo $mediaGallery->mediaGallery('', 60, false);
		$value	= 'filter';
		$text	= 'View more media';
	}

	if($mediaGallery->total > 3){
		echo "<form method='post' style='text-align: center; padding-bottom:10px; $mediaGallery->style'>";
			echo "<button class='small button' name='switch-gallery' value='$value'>$text</button>";
		echo "</form>";
	}
}

function showRelevantPages(){
	if(!empty(get_children(['post_parent' =>get_the_ID()]))){
		$cats['location']	= ['locations'=>[]];

		$categories	= get_the_terms(get_the_ID(), 'locations');

		foreach($categories as $cat){
			if(count($categories) > 1 && $cat->slug == 'ministry'){
				continue;
			}
			
			$cats['location']['locations'][]	= $cat->slug;
		}

		
		$gradient		= SIM\getModuleOption(MODULE_SLUG, 'gallery-background-color-gradient');

		echo SIM\PAGEGALLERY\pageGallery('Related Ministries', [get_post_type()], 3, $cats, 60, true, SIM\getModuleOption(MODULE_SLUG, 'page-gallery-background-color'), $gradient);
	}
}

/* function addGallery($mediaGallery){
	$content	= "<!-- wp:gallery {'linkTo':'none'} -->";
		$content	.= "<figure class='wp-block-gallery has-nested-images columns-default is-cropped'>";
			foreach($mediaGallery->posts as $post){
				$url	= wp_get_attachment_image_url($post->ID);
				$content	.= "<!-- wp:image {'id':$post->ID,'sizeSlug':'large','linkDestination':'media'} -->";
					$content	.= "<figure class='wp-block-image size-large'>";
						$content	.= "<img src='$url' alt='' class='wp-image-$post->ID'/>";
					$content	.= "</figure>";
				$content	.= "<!-- /wp:image -->";
			}
		$content	.= "</figure>";
	$content	.= "<!-- /wp:gallery -->";

	$html	= '';
	$blocks = parse_blocks( $content );
	foreach($blocks as $block){
		$html	.= render_block($block);
	}

	echo $html;
} */