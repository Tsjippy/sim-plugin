<?php
namespace SIM\FRONTENDPOSTING;
use SIM;

add_action( 'rest_api_init', function () {
	// get_attachment_contents
	register_rest_route( 
		RESTAPIPREFIX.'/frontend_posting', 
		'/get_attachment_contents', 
		array(
			'methods' 				=> 'POST',
			'callback' 				=> __NAMESPACE__.'\getAttachmentContents',
			'permission_callback' 	=> '__return_true',
			'args'					=> array(
				'attachment_id'		=> array(
					'required'	=> true,
					'validate_callback' => 'is_numeric'
				),
			)
		)
	);

	// add_category
	register_rest_route( 
		RESTAPIPREFIX.'/frontend_posting', 
		'/add_category', 
		array(
			'methods' 				=> 'POST',
			'callback' 				=> __NAMESPACE__.'\addCategory',
			'permission_callback' 	=> '__return_true',
			'args'					=> array(
				'cat_name'		=> array('required'	=> true),
				'cat_parent'	=> array(
					'required'	=> true,
					'validate_callback' => 'is_numeric'
				),
				'post_type'		=> array(
					'required'	=> true,
					'validate_callback' => function($param) {
						return in_array($param, get_post_types());
					}
				),
			)
		)
	);

	//submit_post
	register_rest_route( 
		RESTAPIPREFIX.'/frontend_posting', 
		'/submit_post', 
		array(
			'methods' 				=> 'POST',
			'callback' 				=> function(){
				global $post;

				$frontEndContent	= new FrontEndContent();
				$result =  $frontEndContent->submitPost();

				if(is_wp_error($result)){
					return $result;
				}

				// Load the updated  post in the loop
				if($result['post']->post_type  == 'change'){
					$p	= $frontEndContent->oldPost->ID;
				}else{
					$p	= $frontEndContent->postId;
				}

				$posts	= new \WP_Query( array( 
					'p'			=> $p, 
					'post_type' => 'any'
				) );

				$GLOBALS['wp_query']= $posts;
				$post				= $posts->post;
				$GLOBALS['post']	= $post;

				if('page' == $post->post_type){
					$type = 'page';
				}else{
					$type = 'single';
				}
				// Find the the correct template
				$baseTemplate	= locate_template(["content-{$type}.php",'content.php']);
				$template 		= apply_filters( "content_template", $baseTemplate, 'content' );

				if(empty($template)){
					$html	= false;
				}else{
					// Get the html from the template
					ob_start();
					include_once($template);
					$html=ob_get_clean();
				}

				// Get the picture
				$result['picture']	= get_the_post_thumbnail_url($post->ID, 'full');

				$result['html'] = do_shortcode($html);

				if($post->post_status == 'pending'){
					$result['url']	= get_preview_post_link($post->ID);
				}else{
					$result['url']	= get_permalink($post->ID);
				}
				
				if($frontEndContent->update){
					do_action('wp_enqueue_scripts');
					ob_start();
					wp_print_scripts();
					print_footer_scripts();
					$result['js']	= ob_get_clean();

					do_action('wp_enqueue_style');
					ob_start();
					wp_print_styles();
					$result['css']	= ob_get_clean();
				}

				return $result;
			},
			'permission_callback' 	=> '__return_true',
			'args'					=> array(
				'post_type'		=> array(
					'required'	=> true,
					'validate_callback' => function($param) {
						return in_array($param, get_post_types());
					}
				),
				'post_title'		=> array(
					'required'	=> true
				),
				'post_content'	=> array(
					'required'	=> true
				),
				'post_author'	=> array(
					'required'	=> true
				),
				'publish_date'	=> array(
					'validate_callback' => function($param) {
						return SIM\isDate($param);
					}
				),
			)
		)
	);

	// remove_post
	register_rest_route( 
		RESTAPIPREFIX.'/frontend_posting', 
		'/remove_post', 
		array(
			'methods' 				=> 'POST',
			'callback' 				=> function(){
				$frontEndContent	= new FrontEndContent();
				return $frontEndContent->removePost();
			},
			'permission_callback' 	=> function(){
				$frontEndContent	= new FrontEndContent();
				return $frontEndContent->fullrights;
			},
			'args'					=> array(
				'post_id'		=> array(
					'required'	=> true,
					'validate_callback' => 'is_numeric'
				)
			)
		)
	);

	// refresh post lock
	register_rest_route( 
		RESTAPIPREFIX.'/frontend_posting', 
		'/refresh_post_lock', 
		array(
			'methods' 				=> 'POST',
			'callback' 				=> function(){
				try{
					if(!function_exists('wp_set_post_lock')){
						include ABSPATH . 'wp-admin/includes/post.php';
					}
					wp_set_post_lock($_POST['postid']);
					return 'Succes';
				}catch (\Exception $e) {
					return $e;
				}
			},
			'permission_callback' 	=> '__return_true',
			'args'					=> array(
				'postid'		=> array(
					'required'	=> true,
					'validate_callback' => 'is_numeric'
				)
			)
		)
	);

	// delete post lock
	register_rest_route( 
		RESTAPIPREFIX.'/frontend_posting', 
		'/delete_post_lock', 
		array(
			'methods' 				=> 'POST',
			'callback' 				=> function(){
				delete_post_meta( $_POST['postid'], '_edit_lock');
				return 'Succes';
			},
			'permission_callback' 	=> '__return_true',
			'args'					=> array(
				'postid'		=> array(
					'required'	=> true,
					'validate_callback' => 'is_numeric'
				)
			)
		)
	);

	// change post type
	register_rest_route( 
		RESTAPIPREFIX.'/frontend_posting', 
		'/change_post_type', 
		array(
			'methods' 				=> 'POST',
			'callback' 				=> function(){
				$frontEndContent	= new FrontEndContent();
				return $frontEndContent->changePostType();
			},
			'permission_callback' 	=> function(){
				$frontEndContent	= new FrontEndContent();
				return $frontEndContent->fullrights;
			},
			'args'					=> array(
				'postid'		=> array(
					'required'	=> true,
					'validate_callback' => 'is_numeric'
				),
				'post_type_selector'		=> array(
					'required'	=> true,
					'validate_callback' => function($param) {
						return in_array($param, get_post_types());
					}
				)
			)
		)
	);

	// Get frontend content form
	register_rest_route( 
		RESTAPIPREFIX.'/frontend_posting', 
		'/post_edit', 
		array(
			'methods' 				=> 'POST',
			'callback' 				=> __NAMESPACE__.'\sendForm',
			'permission_callback' 	=> function(){
				return allowedToEdit($_REQUEST['postid']);
			},
			'args'					=> array(
				'postid'		=> array(
					'required'			=> true,
					'validate_callback' => 'is_numeric'
				)
			)
		)
	);

	// Get post
	register_rest_route( 
		RESTAPIPREFIX.'/frontend_posting', 
		'/post_result', 
		array(
			'methods' 				=> 'POST',
			'callback' 				=> __NAMESPACE__.'\sendPost',
			'permission_callback' 	=> '__return_true',
			'args'					=> array(
				'postid'		=> array(
					'required'	=> true,
					'validate_callback' => 'is_numeric'
				)
			)
		)
	);
} );

/**
 * Converts a files contents to html
 */
function getAttachmentContents(\WP_REST_Request $request ){
	$path	= get_attached_file($request['attachment_id']);

	if(!file_exists($path)){ 
		return new \WP_Error('frontendposting', "File $path does not exist!");
	}

	return SIM\readTextFile($path);
}

/**
 * Add a new category to a post type
 */
function addCategory(\WP_REST_Request $request ){
	$name		= $request->get_param('cat_name');
	$parent		= $request->get_param('cat_parent');
	$postType	= $request->get_param('post_type');

	$taxonomy	= get_object_taxonomies($postType)[0];
	
	$args 		= ['slug' => strtolower($name)];
	if(is_numeric($parent)){
		$args['parent'] = $parent;
	}
	
	$result 	= wp_insert_term( ucfirst($name), $taxonomy, $args);

	if(is_wp_error($result)){
		return $result;
	}

	do_action('sim_after_category_add', $postType, strtolower($name), $result);
	
	if(is_wp_error($result)){
		return new \WP_Error('Event Cat error', $result->get_error_message(), ['status' => 500]);
	}else{
		return [
			'id'		=> $result['term_id'],
			'message'	=> "Added $name succesfully as a $postType category"
		];
	}
}

/**
 * Get the front posting form
 */
function sendForm(){
	do_action('wp_enqueue_scripts');
	wp_enqueue_media();
	
	wp_enqueue_editor();

	$frontEndContent			= new FrontEndContent();
	$frontEndContent->postId	= $_REQUEST['postid'];
	$html						= $frontEndContent->frontendPost(true);

	\_WP_Editors::enqueue_scripts();
	ob_start();
	wp_print_scripts(["sim_frontend_script"]);
	print_footer_scripts();
	\_WP_Editors::editor_js();
	wp_print_media_templates();
	$js	= ob_get_clean();

	do_action('wp_enqueue_style');
	ob_start();
	wp_print_styles();
	$css	= ob_get_clean();

	return [
		'html'	=>$html,
		'js'	=> $js,
		'css'	=> $css
	];
}

/**
 * Gets a post
 */
function sendPost(){
	$postId	= $_REQUEST['postid'];

	// Get the picture
	$url	= get_the_post_thumbnail_url($postId, 'full');

	// Get  the content
	$content = apply_filters( 'the_content', get_the_content() );

	return [
		'url'		=> $url,
		'content'	=> $content
	];
}