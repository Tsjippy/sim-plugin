<?php
namespace SIM\FORMS;
use SIM;

add_action('init', function () {
	register_block_type(
		__DIR__ . '/formselector/build',
		array(
			'render_callback' => __NAMESPACE__.'\showFormSelector',
		)
	);

	register_block_type(
		__DIR__ . '/formbuilder/build',
		array(
			'render_callback' => function($request){
				return showFormBuilder($request)['html'];
			},
		)
	);

	register_block_type(
		__DIR__ . '/formresults/build',
		array(
			'render_callback' => __NAMESPACE__.'\showFormResults',
			'attributes'      => [
				'formid' => [
					'type' => 'string'
				],
				'onlyOwn'  => [
					'type'  => 'boolean',
					'default' => false,
				],
				'archived'  => [
					'type'  => 'boolean',
					'default' => false,
				],
				'tableid'  => [
					'type'  => 'integer'
				],
			]
		)
	);

	register_block_type(
		__DIR__ . '/missing_form_fields/build',
		array(
			'render_callback' => __NAMESPACE__.'\missingFormFields',
			'attributes'      => [
				'type'  => [
					'type'  	=> 'string',
					'default' 	=> 'mandatory',
				]
			]
		)
	);
});

add_action( 'enqueue_block_assets', function(){
	if(is_admin()){
		SIM\enqueueScripts();

		SIM\FILEUPLOAD\registerUploadScripts();

		registerScripts();
		
		wp_enqueue_script( 'sim_formbuilderjs');

		wp_enqueue_script('sim_forms_table_script');
	}
} );