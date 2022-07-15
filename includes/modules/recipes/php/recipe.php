<?php
namespace SIM\RECIPES;
use SIM;

/*
	In this file we define a new post type: recipe
	We also define a new taxonomy (category): recipes
	We make sure post of this type get an url according to their taxonomy
*/

add_action('init', function(){
	/*
		CREATE RECIPE POST TYPE
	*/
	SIM\registerPostTypeAndTax('recipe','recipes');
}, 999);

add_filter( 'widget_categories_args', function ( $catArgs ) {
	//if we are on a recipes page, change to display the recipe types
	if(is_tax('recipes') || is_page('recipes') || get_post_type()=='recipe'){
		$catArgs['taxonomy'] 		= 'recipes';
		$catArgs['hierarchical']	= true;
		$catArgs['hide_empty'] 		= false;
	}
		
    return $catArgs;
});

//Add to frontend form
add_action('sim_frontend_post_before_content', __NAMESPACE__.'\recipeSpecificFields');
add_action('sim_frontend_post_content_title', __NAMESPACE__.'\recipeTitle');
add_action('sim_after_post_save', __NAMESPACE__.'\storeRecipeMeta', 10, 2);

add_filter('sim_frontend_posting_modals', function($types){
	$types[]	= 'recipe';
	return $types;
});

function recipeTitle($postType){
	//Recipe content title
	$class = 'recipe';
	if($postType != 'recipe'){
		$class .= ' hidden';
	}
	
	echo "<h4 class='$class' name='recipe_content_label'>";
		echo 'Recipe instructions (one per line)';
	echo "</h4>";
}

function storeRecipeMeta($post){
	//store categories
    $cats = [];
    if(is_array($_POST['recipes_ids'])){
        foreach($_POST['recipes_ids'] as $catId) {
            if(is_numeric($catId)){
				$cats[] = $catId;
			}
        }
        
        //Store types
        $cats = array_map( 'intval', $cats );
        
        wp_set_post_terms($post->ID, $cats, 'recipes');
    }
	
	//ingredients
	if(isset($_POST['ingredients'])){
		$ingredients = wp_kses_post($_POST['ingredients']);
		
		//find any fractions in the text and replace them with decimal values
		$ingredients 	= preg_replace_callback(
			//0 or more numbers followed by 0 or more spaces followed by 1 or more digits followed by a / followed by 1 or more digits
			'/([0-9]*)(\s*)([0-9]+)\/([0-9]+)/m', 
			function($matches){
				$fraction 			= round($matches[3] / $matches[4], 2); //max 2 decimals
				$preceedingNumber 	= $matches[1];
				$space				= $matches[2];
				
				//If there is a number before the fraction
				if($preceedingNumber != ''){
					//combine the two
					$value = $preceedingNumber + $fraction;
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
		if(empty($ingredients)){
			delete_post_meta($post->ID, 'ingredients');
		}else{
			update_metadata( 'post', $post->ID, 'ingredients', $ingredients);
		}
	}
	
	//time_needed
	if(isset($_POST['time_needed'])){
		if(is_numeric($_POST['time_needed'])){
			//Store time_needed
			update_metadata( 'post', $post->ID, 'time_needed', $_POST['time_needed']);
		}else{
			delete_post_meta($post->ID, 'time_needed');
		}
	}
	
	//serves
	if(isset($_POST['serves'])){
		if(is_numeric($_POST['serves'])){
			//Store serves
			update_metadata( 'post', $post->ID, 'serves', $_POST['serves']);
		}else{
			delete_post_meta($post->ID, 'serves');
		}
	}
}

function recipeSpecificFields($frontEndContent){
	$categories	= get_categories( array(
		'orderby' => 'name',
		'order'   => 'ASC',
		'taxonomy'=> 'recipes',
		'hide_empty' => false,
	) );
	
	$frontEndContent->showCategories('recipe', $categories);
	?>
	<div class="recipe <?php if($frontEndContent->post_type != 'recipe'){echo 'hidden';} ?>">
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

add_action('sim_before_page_print', function($post, $pdf){
	//If recipe
	if($post->post_type == 'recipe'){
		$pdf->printImage(get_the_post_thumbnail_url($post),-1,20,-1,-1,true,true);

		$baseUrl	= plugins_url('pictures', __DIR__);
		
		//Duration
		$url = "{$baseUrl}/time.png";
		$pdf->printImage($url,10,-1,10,10);
		$pdf->write(10,get_post_meta($post->ID,'time_needed',true).' minutes');
		
		//Serves
		$url = "{$baseUrl}/recipe_serves.png";
		$pdf->printImage($url,55,-1,10,10);
		
		$persons = get_post_meta(get_the_ID(),'serves',true);
		if($persons == 1){
			$person_text = 'person';
		}else{
			$person_text = 'people';
		}
		
		$pdf->write(10,"$persons $person_text");
		
		$pdf->Ln(15);
		$pdf->writeHTML('<b>Ingredients:</b>');
		$pdf->Ln(5);
		$ingredients = explode("\n", trim(get_post_meta(get_the_ID(),'ingredients',true)));
		foreach($ingredients as $ingredient){
			$pdf->write(10,chr(127).' '.$ingredient);
			$pdf->Ln(5);
		}
		
		$pdf->Ln(10);
		$pdf->writeHTML('<b>Instructions:</b>');
	}
});