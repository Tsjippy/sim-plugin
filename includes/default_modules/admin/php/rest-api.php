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
				'module-name'		=> array(
					'required'	=> true
				)
			)
		)
	);
});

function getChangelog(){
	if(empty($_POST['module-name'])){
		return;
	}

    $github		= new SIM\GITHUB\Github();

    $moduleName = sanitize_text_field(wp_unslash($_POST['module-name']));

    $release    = $github->getFileContents('tsjippy', $moduleName, 'CHANGELOG.md');
    if($release){
        return $release;
    }
    
    return "Unable to fetch changelog";
}