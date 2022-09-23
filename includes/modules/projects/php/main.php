<?php
namespace SIM\PROJECTS;
use SIM;

// Create the location custom post type 
add_action('init', function(){
	SIM\registerPostTypeAndTax('project', 'projects');
}, 999);
