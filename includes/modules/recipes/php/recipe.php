<?php
namespace SIM\RECIPES;
use SIM;

/*
	In this file we define a new post type: recipe
	We also define a new taxonomy (category): recipetype
	We make sure post of this type get an url according to their taxonomy
*/

add_action('init', function(){
	/*
		CREATE RECIPE POST TYPE
	*/
	SIM\register_post_type_and_tax('recipe','recipes');
}, 999);

//Add recipe type via AJAX
add_action('wp_ajax_add_recipe_type',function(){
	SIM\verify_nonce('add_recipe_type_nonce');
	
	$name		= sanitize_text_field($_POST['recipe_type_name']);
	$parent		= $_POST['recipe_type_parent'];
	
	$args 		= ['slug' => strtolower($name)];
	if(is_numeric($parent)) $args['parent'] = $parent;
	
	$result = wp_insert_term( ucfirst($name), 'recipetype',$args);
	
	if(is_wp_error($result)){
		wp_die($result->get_error_message(),500);
	}else{
		wp_die(json_encode(
			[
				'id'		=> $result['term_id'],
				'name'		=> $name,
				'type'		=> 'recipe',
				'message'	=> "Added $name succesfully as recipe category",
				'callback'	=> 'cat_type_added'
			]
		));
	}
});

add_filter( 'widget_categories_args', function ( $cat_args, $instance  ) {
	//if we are on a recipetype page, change to display the recipe types
	if(is_tax('recipetype') or is_page('recipes') or get_post_type()=='recipe'){
		$cat_args['taxonomy'] 		= 'recipetype';
		$cat_args['hierarchical']	= true;
		$cat_args['hide_empty'] 	= false;
	}
		
    return $cat_args;
}, 10, 2 );

//Add to frontend form
add_action('frontend_post_before_content', 'SIM\RECIPES\recipe_specific_fields');
add_action('frontend_post_content_title', 'SIM\RECIPES\recipe_title');
add_action('sim_after_post_save', 'SIM\RECIPES\store_recipe_meta',10,2);

add_filter('sim_frontend_posting_modals', function($types){
	$types[]	= 'recipe';
	return $types;
});

function recipe_title($post_type){
	//Recipe content title
	$class = 'recipe';
	if($post_type != 'recipe')	$class .= ' hidden';
	
	echo "<h4 class='$class' name='recipe_content_label'>";
		echo 'Recipe instructions (one per line)';
	echo "</h4>";
}

function store_recipe_meta($post, $post_type){
	//store recipetype
	$recipetypes = [];
	if(is_array($_POST['recipetype'])){
		foreach($_POST['recipetype'] as $key=>$recipetype) {
			if($recipetype != '') $recipetypes[] = $recipetype;
		}
		
		//Store recipe type
		wp_set_post_terms($post->ID,$recipetypes,'recipetype');
	}
	
	//ingredients
	if(isset($_POST['ingredients'])){
		$ingredients = wp_kses_post($_POST['ingredients']);
		
		//find any fractions in the text and replace them with decimal values
		$ingredients 	= preg_replace_callback(
			//0 or more numbers followed by 0 or more spaces followed by 1 or more digits followed by a / followed by 1 or more digits
			'/([0-9]*)(\s*)([0-9]+)\/([0-9]+)/m', 
			function($matches){
				$fraction 			= round($matches[3]/$matches[4],2); //max 2 decimals
				$preceeding_number 	= $matches[1];
				$space				= $matches[2];
				
				//If there is a number before the fraction
				if($preceeding_number != ''){
					//combine the two
					$value = $preceeding_number + $fraction;
					//do not return the space
					$space = '';
				}else{
					$value =  $fraction;
				}
				return $space.$value;
			}, 
			$ingredients
		);
		
		//put all the numbers in a span including a data attribute for their original value
		$ingredients 	= preg_replace_callback(
			//1 or more numbers followed by 0 or more dots followed by 0 or more digits followed by 0 or more space followed by 0 or more letters
			'/([0-9]+\.*[0-9]*)\s*([a-zA-z]*)/m', 
			function($matches){
				$value 	= $matches[1];
				$word 	= $matches[2];
				$text 	= $matches[0];
				return "<span class='recipe-amount' data-value='$value' data-word='$word'>$text</span>";
			}, 
			$ingredients
		);
		
		//Store ingredients
		update_post_meta($post->ID,'ingredients',$ingredients);
	}
	
	//time_needed
	if(isset($_POST['time_needed']) and is_numeric($_POST['time_needed'])){
		//Store time_needed
		update_post_meta($post->ID,'time_needed',$_POST['time_needed']);
	}
	
	//serves
	if(isset($_POST['serves']) and is_numeric($_POST['serves'])){
		//Store serves
		update_post_meta($post->ID,'serves',$_POST['serves']);
	}
}

function recipe_specific_fields($frontEndContent){
	$categories	= get_categories( array(
		'orderby' => 'name',
		'order'   => 'ASC',
		'taxonomy'=> 'recipetype',
		'hide_empty' => false,
	) );
	
	$frontEndContent->show_categories('recipe', $categories);
	?>
	<div class="recipe <?php if($frontEndContent->post_type != 'recipe') echo 'hidden'; ?>">
		<h4 name="ingredients_label">Recipe ingredients (one per line)</h4>
		<textarea name="ingredients" rows="10">
			<?php echo wp_strip_all_tags(get_post_meta($frontEndContent->post_id,'ingredients',true)); ?>
		</textarea>
		
		
		<label class="block" name="time_need_label">
			<h4>Recipe time needed</h4>
			<input type='number' name="time_needed" min="1" value="<?php echo get_post_meta($frontEndContent->post_id,'time_needed',true); ?>" style="display: inline-block;"> 
			<span style="margin-left:-100px;">minutes</span>
		</label>

		<label class="block" name="serves_label">
			<h4>Serves</h4>
			<input type='number' name="serves" min="1" value="<?php echo get_post_meta($frontEndContent->post_id,'serves',true); ?>" style="display: inline-block;"> 
			<span style="margin-left:-100px;">people</span>
		</label>
	</div>
	<?php
}
