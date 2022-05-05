<?php
namespace SIM\FRONTEND_POSTING;
use SIM;

add_action( 'rest_api_init', function () {
	// get_attachment_contents
	register_rest_route( 
		'sim/v1/frontend_posting', 
		'/get_attachment_contents', 
		array(
			'methods' 				=> 'POST',
			'callback' 				=> __NAMESPACE__.'\get_attachment_contents',
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
					'validate_callback' => function($param, $request, $key) {
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
				return $frontEndContent->submit_post();
			},
			'permission_callback' 	=> '__return_true',
			'args'					=> array(
				'post_type'		=> array(
					'required'	=> true,
					'validate_callback' => function($param, $request, $key) {
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
					'required'	=> true,
					'validate_callback' => function($param) {
						return SIM\is_date($param);
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
				return $frontEndContent->remove_post();
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
				if(!function_exists('wp_set_post_lock')){
					include ABSPATH . 'wp-admin/includes/post.php';
				}
				wp_set_post_lock($_POST['postid']);
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
				return $frontEndContent->change_post_type();
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

function get_attachment_contents(\WP_REST_Request $request ){
	$path	= get_attached_file($request['attachment_id']);

	if(!file_exists($path)) return;

	$ext 	= pathinfo($path, PATHINFO_EXTENSION);
		
	if($ext == 'docx'){
		$reader = 'Word2007';
	}elseif($ext == 'doc'){
		$reader = 'MsDoc';
	}elseif($ext == 'rtf'){
		$reader = 'rtf';
	}elseif($ext == 'txt'){
		$reader = 'plain';
	}else{
		$reader = 'Word2007';
	}
	
	if($reader == 'plain'){
		$file = fopen($path, "r");
		$contents =  fread($file,filesize($path));
		fclose($file);
		
		return str_replace("\n", '<br>', $contents);
	}else{
		//Load the filecontents
		$phpWord = \PhpOffice\PhpWord\IOFactory::createReader($reader)->load($path);

		//Convert it to html
		$htmlWriter = new \PhpOffice\PhpWord\Writer\HTML($phpWord);
		
		$html = $htmlWriter->getWriterPart('Body')->write();
		
		$html = preg_replace_callback(
			//get all tags which are followed by the same tag 
			//syntax: <(some tagname)>(some text)</some tagname)0 or more spaces<(use tagname as found before + some extra symbols)>
			'/<([^>]*)>([^<]*)<\/(\w+)>\s*<(\3[^>]*)>/m', 
			function($matches){
				//print_array($matches,true);
				//If the opening tag is exactly like the next opening tag, remove the the duplicate
				if($matches[1] == $matches[4] and ($matches[3] == 'span' or $matches[3] == 'strong' or $matches[3] == 'b')){
					return $matches[2];
				}else{
					return $matches[0];
				}
			}, 
			$html
		);
		
		//Return the contents
		return $html;
	}
}

function addCategory(\WP_REST_Request $request ){
	$name		= $request->get_param('cat_name');
	$parent		= $request->get_param('cat_parent');
	$post_type	= $request->get_param('post_type');
	
	$args 		= ['slug' => strtolower($name)];
	if(is_numeric($parent)) $args['parent'] = $parent;
	
	$result = wp_insert_term( ucfirst($name), $post_type."s", $args);

	do_action('sim_after_category_add', $post_type, strtolower($name), $result);
	
	if(is_wp_error($result)){
		return new \WP_Error('Event Cat error', $result->get_error_message(), ['status' => 500]);
	}else{
		return [
			'id'		=> $result['term_id'],
			'message'	=> "Added $name succesfully as en $post_type category"
		];
	}
}