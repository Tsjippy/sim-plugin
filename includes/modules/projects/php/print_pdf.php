<?php
namespace SIM\PROJECTS;
use SIM;

add_action('sim-before-print-content', function($post, $pdf){
    if($post->post_type != 'project'){
        return;
    }
    
    $pdf->printImage(get_the_post_thumbnail_url($post), -1, 20, -1, -1, true, true);
		
    //Project number
    $url = plugins_url('pictures/project.png', __DIR__);

    $pdf->printImage($url, 10, -1, 10, 10);
    $pdf->write(10, get_post_meta(get_the_ID(), 'number', true));
    
    $manager = get_post_meta(get_the_ID(), 'manager', true);

    //Manager name
    $url = plugins_url('pictures/manager.png', __DIR__);
    $pdf->printImage($url, 55, -1, 10, 10);    
    $pdf->write(10, $manager['name']);

    //Manager tel
    $url = plugins_url('pictures/tel.png', __DIR__);
    $pdf->printImage($url, 100, -1, 10, 10);    
    $pdf->write(10, $manager['tel']);

    //Manager e-mail
    if(!empty($manager['email'])){
        $url    = plugins_url('pictures/email.png', __DIR__);
        $y      = $pdf->getY()+12;
        $pdf->printImage($url, 10, $y, 10, 10);    
        $pdf->write(10, $manager['email']);
    }
    
    //Url
    $imageUrl = plugins_url('pictures/url.png', __DIR__);
    $y      = $pdf->getY()+12;
    $url    = get_post_meta(get_the_ID(), 'url', true);
    if(!empty($url)){
        $pdf->printImage($imageUrl, 10, $y, 10, 10);    
        $pdf->write(10, $url);
    }

    $pdf->Ln(20);
    $pdf->writeHTML('<b>Description:</b>');
}, 10, 2);