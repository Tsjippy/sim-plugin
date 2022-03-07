<?php
namespace SIM\FRONTPAGE;
use SIM;

//Remove the featured image from the page and post content
add_action( 'init', function() {
	remove_action( 'generate_after_header', 'generate_featured_page_header', 10 );
	remove_action( 'generate_before_content', 'generate_featured_page_header_inside_single', 10 );
	
	//Remove archive title from archive pages
	remove_action( 'generate_archive_title', 'generate_archive_title' );
});

//Do not display page title
add_action( 'after_setup_theme', function() {
	//on all page except the newspage
	add_filter( 'generate_show_title', function (){
		if ( is_home() or is_search() or is_category() or is_tax()) {
			//Show the title on the news page
			return true;
		}else{
			//Hide the title
			return false;
		}
	});
});

//Removes the 'Protected:' part from posts titles
add_filter( 'protected_title_format', function () {
	return __('%s');
});

//Add a title section below the menu
add_action('generate_after_header',function (){
	global $post;
	global $Modules;
	if($post){		
		$title = $post->post_title;
	}else{
		$title = '';
	}
		
	//If this page is the news page
	if ( is_home()) {
		$title = "News";
	//Or an archive page (category of news)
	}elseif(is_category() or is_tax()){
		$category = get_queried_object();
		$title = $category->name;
		if(is_tax('recipetype')){
			$title .= ' recipies';
		}elseif(is_tax('eventtype')){
			$title .= ' events';
		}elseif(is_tax('locationtype')){
			//nothing
		}else{
			$title .= " posts";
		}
	}
	
	//change title of all pages except the frontpage
	if($title != 'Home' and !is_page($Modules['login']['home_page'])){
		//Display featured image in title if it has one
		if ( has_post_thumbnail() and $title != "News" and !is_category() and !is_tax() and get_post_type() != 'recipe') {
			echo '<div id="page-title-image" style="background-image: url('.get_the_post_thumbnail_url().');"></div>';
		}
		//Add the title
		echo '<div id="page-title-div">';
		echo '<h2 id="page-title">'.$title.'</h2>';
		echo '</div>';
	}
});