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
			$childPageHtml .= '<li><a href="'.$childPage['guid'].'">'.$childPage['post_title']."</a></li>";
		}
		$childPageHtml .= "</ul>";
	}		
	
	$latitude 	= get_post_meta($postId,'geo_latitude',true);
	$longitude 	= get_post_meta($postId,'geo_longitude',true);
	if (!empty($latitude) && !empty($longitude)){
		$html .= "<p><a class='button' onclick='Main.getRoute(this,$latitude,$longitude)'>Get directions to $ministry</a></p>";
	}
	
	if(!empty($childPageHtml)){
		$html = $childPageHtml."<br><br>".$html; 
	}

	$html	   .= getLocationEmployees($postId);

	return $html;	
}

/**
 * Get the people that work at a certain location
 * 
 * @param 	int	$postId		The WP_Post id
 */
function getLocationEmployees($post){
	if (!is_user_logged_in()){
		return '';
	}

	if(is_numeric($post)){
		$post	= get_post($post);
	}

	$locations		= array_keys(get_children(array(
		'post_parent'	=> $post->ID,
		'post_type'   	=> 'location',
		'post_status' 	=> 'publish',
	)));
	$locations[]	= $post->ID;

	//Loop over all users to see if they work here
	$users 			= get_users('orderby=display_name');
	
	$html 			= "";

	foreach($users as $user){
		$userLocations 	= (array)get_user_meta( $user->ID, "jobs", true);

		$intersect		= array_intersect(array_keys($userLocations), $locations);
	
		//If a user works for this ministry, echo its name and position
		if ($intersect){
			$userPageUrl		= SIM\maybeGetUserPageUrl($user->ID);
			$privacyPreference	= (array)get_user_meta( $user->ID, 'privacy_preference', true );
			
			if(!isset($privacyPreference['hide_ministry'])){
				$html .=	"<div class='person-wrapper'>";
					if(!isset($privacyPreference['hide_profile_picture'])){
						$html .= SIM\displayProfilePicture($user->ID);
						$style = "";
					}else{
						$style = ' style="margin-left: 55px; padding-top: 30px; display: block;"';
					}
					
					$pageUrl = "<a class='user_link' href='$userPageUrl'>$user->display_name</a>";
					foreach($intersect as $postId){
						$html .= "   <div $style>$pageUrl <br>({$userLocations[$postId]})</div>";
					}
				$html .= '</div>';
			}					
		}
	}
	

	if(empty($html)){
		$html .= "No one dares to say they are working here!";
	}else{

		$style	= "<style>";
			$style	.= ".person-wrapper{margin: 0px 10px 10px 0px;display:flex;width:25%;}";
			$style	.= ".profile-picture{max-height:50px;}";
		$style	.= "</style>";
		$html	= "$style<div class='employee-gallery' style='display:flex;flex-wrap:wrap'>$html</div>";
	}

	$html 	= "<p style='padding:10px;'><strong>People working at $post->post_title are:</strong><br><br>$html</p>";
	
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