<?php
namespace SIM;

// Load the js file to filter all blocks
add_action( 'enqueue_block_editor_assets', function() {
    wp_register_script(
        'sim-block-filter',
        plugins_url('blocks/block_filters/build/index.js', __DIR__),
        [ 'wp-blocks', 'wp-dom', 'wp-dom-ready', 'wp-edit-post' ],
        STYLE_VERSION
    );
    wp_enqueue_script( 'sim-block-filter' );
});

// Filter block visibility
add_filter('render_block', function($blockContent, $block){
	if(
		// not on a specific page
		(
			!empty($block['attrs']['onlyOn']) && 
			!in_array(get_the_ID(), $block['attrs']['onlyOn'])
		)	||
		// or not logged in
		(
			isset($block['attrs']['onlyLoggedIn']) && 
			!is_user_logged_in()
		)
	){
		return '';
	}

	// Run php filters
	if(!empty($block['attrs']['phpFilters']) ){
		$show	= false;
		foreach($block['attrs']['phpFilters'] as $filter){
			if(function_exists($filter) && $filter(get_the_ID())){
				$show	= true;
				break;
			}
		}

		if(!$show){
			return '';
		}
	}

	// add hide on mobile class
	if(isset($block['attrs']['hideOnMobile']) && $block['attrs']['hideOnMobile']){
		return "<span class='hide-on-mobile'>$blockContent</span>";
	}

	return $blockContent;
}, 10, 2);

add_action('init', function () {
	register_block_type(
		__DIR__ . '/show_categories/build',
		array(
			'render_callback' => __NAMESPACE__.'\displayCategories',
		)
	);

	register_block_type(
		__DIR__ . '/show_children/build',
		array(
			'render_callback' => __NAMESPACE__.'\displayChildren',
			'attributes'      => [
				'grandchildren' => [
					'type' 		=> 'boolean',
					'default'	=> false
				],
				'parents' => [
					'type' 		=> 'boolean',
					'default'	=> true
				],
				'grantparents' => [
					'type' 		=> 'int',
					'default'	=> 2
				],
			]
		)
	);
});

function displayCategories($attributes) {

	$args = wp_parse_args($attributes, array(
		'count' 		=> false
	));

	if(is_home()){
		$taxonomy	= 'category';
	}elseif(is_archive()){

		if(isset(get_queried_object()->taxonomy)){
			$taxonomy	= get_queried_object()->taxonomy;
		}else{
			$taxonomy	= get_queried_object()->taxonomies[0];
		}
	}elseif(is_tax()){
		$taxonomy	= '';
	}else{
		// We are on place without categories
		return '';
	}

	return	'<style> .widget li {list-style-type: none;}</style>'.wp_list_categories( array(
		'echo'				=> 0,
		'taxonomy' 			=> $taxonomy,
		'current_category'	=> get_queried_object()->term_id,
		'show_count'		=> $args['count'],
		'title_li' 			=> '<h4>' . __( 'Categories', 'sim' ) . '</h4>'
	));
}

function displayChildren($attributes) {
	if(!is_page()){
		return '';
	}

	$depth	= 1;
	if($attributes['grandchildren']){
		$depth	= 0;
	}

	$parentId	= get_the_ID();

	if(has_post_parent($parentId)){
		if($attributes['grantparents']){
			$ancestors	= get_post_ancestors($parentId);
			$level		= min($attributes['grantparents'], count($ancestors))-1;
			$parentId	= $ancestors[$level];
		}elseif($attributes['parents']){
			$parentId	= wp_get_post_parent_id($parentId);
		}
	}

	$script	= "<script>";
		$script	.= "document.addEventListener('DOMContentLoaded', () => {";
			$script	.= "document.querySelectorAll('.childpost .current_page_ancestor > .children.hidden').forEach(el=>el.classList.remove('hidden'));";
			$script	.= "button = document.createElement('button');";
			$script	.= "button.innerText = '+';";
			$script	.= "button.classList.add('button');";
			$script	.= "button.classList.add('small');";
			$script	.= "button.classList.add('expand-children');";
			$script	.= "document.querySelectorAll('.childpost .page_item_has_children > a').forEach(el=>el.parentNode.insertBefore(button.cloneNode(true), el.nextSibling));";
			$script	.= "document.querySelectorAll('.childpost .current_page_ancestor > .expand-children').forEach(el=>el.textContent = '-');";
			$script	.= "document.addEventListener('click', ev=>{";
				$script	.= "if(ev.target.matches('.expand-children')){";
					$script	.= "ev.target.closest('li').querySelector('.children').classList.toggle('hidden');";
					$script	.= "if(ev.target.textContent == '-'){";
						$script	.= "ev.target.textContent = '+'";
					$script	.= "}else{";
						$script	.= "ev.target.textContent = '-'";
					$script	.= "}";
				$script	.= "}";
			$script	.= "});";
		$script	.= "})";
	$script	.= "</script>";

	$html	= wp_list_pages(array(
		'depth'			=> $depth,
		'child_of' 		=> $parentId,
		'echo'			=> false,
		'post_type'		=> get_post_type(),
		'title_li'		=> null,
		'hierarchical' => true,
	));

	if(!empty($html)){
		$html	= str_replace("class='children'", "class='children hidden'", $html);
		$url	= get_permalink(($parentId));
		$title	= get_the_title($parentId);
		return "<div class='childpost'><h4><a href='$url'>$title</a></h4><ul>$html</ul></div>$script";
	}
}

/**
 * Creates children html
 * 
 * @param	int		$postId		The postId of the post to get children for
 * @param	boolean	$recursive	Whether or not to add children of children
 */
function getGrantChildren($postId, $recursive, $level=1){
	$html		= '';
	$children	= get_children($postId);
	if(empty($children)){
		return '';
	}

	$html	.= "<ul>";
	foreach($children as $child){
		$url	= get_permalink($child->ID);
		$html	.= "<li><a href='$url'>{$child->post_title}</a></li>";

		if($recursive){
			$html	.= getGrantChildren($child->ID, $level+1);
		}
	}
	$html	.= "</ul>";

	return $html;
}