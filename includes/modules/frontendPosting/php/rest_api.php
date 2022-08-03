<?php
namespace SIM\FRONTENDPOSTING;
use SIM;

add_action( 'rest_api_init', function () {
	// get_attachment_contents
	register_rest_route( 
		'sim/v1/frontend_posting', 
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
		'sim/v1/frontend_posting', 
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
		'sim/v1/frontend_posting', 
		'/submit_post', 
		array(
			'methods' 				=> 'POST',
			'callback' 				=> function(){
				$frontEndContent	= new FrontEndContent();
				return $frontEndContent->submitPost();
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
		'sim/v1/frontend_posting', 
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
		'sim/v1/frontend_posting', 
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
		'sim/v1/frontend_posting', 
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
		'sim/v1/frontend_posting', 
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