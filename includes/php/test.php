<?php
namespace SIM;

use mikehaertl\shellcommand\Command;

//Shortcode for testing
add_shortcode("test",function ($atts){
    global $wpdb;
    ob_start();
    ?>
    <h1 style="color:green">GeeksforGeeks</h1>
    <img id='picture' src="https://localhost/simnigeria/wp-content/uploads/private/profile_pictures/Ewaldh-2021-05-16 18-21-25IMG_6936.JPG" alt="picture">
    
    
    <canvas id="GFG" width="100" height="300" style="border:2px solid gray;height: auto;max-width: 100%;"></canvas>
 
    <script>
        let width   = 1072;
        let height  = 582;
        let x       = 2067;
        let y       = 582;
        let orgImage    = document.getElementById('picture');

        console.log(orgImage);

        var canvas = document.getElementById("GFG");

        canvas.width    = height;
        canvas.height   = width;

        var context = canvas.getContext("2d");

        context.translate((height/2), (width/2));
        context.rotate((-90) * Math.PI / 180); // x and y are now swapped
        context.translate(-(width/2), -(height/2));

        context.drawImage(orgImage, x, y, width, height, 0, 0,  width, height);
        


    </script>
     <?php

     return ob_get_clean();


/*     $posts = get_posts(
		array(
			'post_type'		=> 'any',
			'author'		=> 137,
			'numberposts'	=> -1,
		)
	);

    foreach($posts as $post){
        wp_update_post(
            [
                'ID'         	=> $post->ID,
				'post_author'	=> 292
            ], 
            false, 
            false
        );
    } */
});

// turn off incorrect error on localhost
add_filter( 'wp_mail_smtp_core_wp_mail_function_incorrect_location_notice', '__return_false' );

