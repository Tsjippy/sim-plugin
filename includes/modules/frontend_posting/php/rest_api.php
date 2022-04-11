<?php
namespace SIM\FRONTEND_POSTING;
use SIM;

add_action( 'rest_api_init', function () {
	//Route for notification messages
	register_rest_route( 
		'sim/v1/frontendposting', 
		'/get_attachment_contents', 
		array(
			'methods' 				=> 'POST',
			'callback' 				=> __NAMESPACE__.'\get_attachment_contents',
			'permission_callback' 	=> '__return_true',
			'args'					=> array(
				'attachment_id'		=> array(
					'required'	=> true,
					'validate_callback' => function($param, $request, $key) {
						return is_numeric( $param );
					}
				),
			)
		)
	);
} );

add_action( 'rest_api_init', function () {
	//Route for notification messages
	register_rest_route( 
		'sim/v1/frontendposting', 
		'/add_category', 
		array(
			'methods' 				=> 'POST',
			'callback' 				=> __NAMESPACE__.'\addCategory',
			'permission_callback' 	=> '__return_true',
			'args'					=> array(
				'cat_name'		=> array('required'	=> true),
				'cat_parent'	=> array(
					'required'	=> true,
					'validate_callback' => function($param, $request, $key) {
						return is_numeric( $param );
					}
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
	SIM\verify_nonce('add_category_nonce');

	$name		= $request->get_param('cat_name');
	$parent		= $request->get_param('cat_parent');
	$post_type	= $request->get_param('post_type');
	
	$args 		= ['slug' => strtolower($name)];
	if(is_numeric($parent)) $args['parent'] = $parent;
	
	$result = wp_insert_term( ucfirst($name), $post_type."type", $args);
	
	if(is_wp_error($result)){
		return new \WP_Error('Event Cat error', $result->get_error_message(), ['status' => 500]);
	}else{
		return [
			'id'		=> $result['term_id'],
			'message'	=> "Added $name succesfully as en $post_type category"
		];
	}
}