<?php
namespace SIM\ADMIN;
use SIM;

add_action( 'rest_api_init', function () {
	//Route for first names
	register_rest_route(
		RESTAPIPREFIX,
		'/get-changelog',
		array(
			'methods'				=> 'POST',
			'callback'				=> __NAMESPACE__.'\getChangelog',
			'permission_callback' 	=> '__return_true',
            'args'					=> array(
				'module_name'		=> array(
					'required'	=> true
				)
			)
		)
	);
});

function getChangelog(){
    $github		= new SIM\GITHUB\Github();

    $moduleName = sanitize_text_field($_POST['module_name']);

    $release    = $github->getFileContents('tsjippy', $moduleName, 'CHANGELOG.md');
    if($release){
        return $release;
    }
    
    return "Unable to fetch changelog";
}