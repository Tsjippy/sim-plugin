<?php
namespace SIM\RECIPES;
use SIM;

add_action('sim-before-print-content', function($post, $pdf){
    if($post->post_type != 'recipe'){
        return;
    }
    
    $pdf->printImage(get_the_post_thumbnail_url($post), -1, 20, -1, -1, true, true);
		
    //Duration
    $url = plugins_url('pictures/time.png', __DIR__);

    $pdf->printImage($url, 10, -1, 10, 10);
    $pdf->write(10,get_post_meta($post->ID, 'time_needed', true).' minutes');
    
    //Serves
    $url = plugins_url('pictures/serves.png', __DIR__);
    $pdf->printImage($url, 55, -1, 10, 10);
    
    $persons = get_post_meta(get_the_ID(), 'serves', true);
    if($persons == 1){
        $personText = 'person';
    }else{
        $personText = 'people';
    }
    
    $pdf->write(10,"$persons $personText");
    
    $pdf->Ln(15);
    $pdf->writeHTML('<b>Ingredients:</b>');
    $pdf->Ln(5);
    $ingredients = explode("\n", trim(get_post_meta(get_the_ID(), 'ingredients', true)));
    foreach($ingredients as $ingredient){
        $pdf->writeHTML(chr(127).' '.$ingredient);
        $pdf->Ln(5);
    }
    
    $pdf->Ln(10);
    $pdf->writeHTML('<b>Instructions:</b>');
}, 10, 2);