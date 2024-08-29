<?php
namespace SIM\USERMANAGEMENT;
use SIM;

add_filter('sim-mailchimp-from', function($adresses){
    return array_merge($adresses, [
        'jos.personnel@sim.org'	        => 'jos.personnel',
        'jos.dirassist@sim.org'	        => 'jos.dirassist',
        'jos.director@sim.org'	        => 'jos.director',
        'jos.health@sim.org'	        => 'jos.health',
        'jos.communications@sim.org'	=> 'jos.communications',
    ]);
});