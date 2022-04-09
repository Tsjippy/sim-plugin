<?php
namespace SIM\PDF;
use SIM;

//only load when checked
if(!SIM\get_module_option('PDF', 'full_screen')) return;

//Show PDFs full screen
add_filter( 'the_content', function ( $content ) {
    $post_id 	= get_the_ID();
    $content	= str_replace('<p>&nbsp;</p>','',$content);
    
    //If the string starts with 0 or more spaces, then a <p> followed by a hyperlink ending in .pdf then the download text ending an optional download button followed with 0 or more spaces.
    $pattern = '/^\s*<p><a href="(.*?\.pdf)">([^<]*<\/a>)(.*\.pdf">Download<\/a>)?<\/p>\s*$/i';
    
    //Execute the regex
    preg_match($pattern, $content, $matches);
    //If an url exists it means there is only a pdf on this page
    if(isset($matches[2])){
        /* IF PEOPLE HAVE TO READ IT, MARK AS READ */
        $audience	= get_post_meta($post_id,"audience",true);
        
        if(!empty($audience)){
            //Get current user id
            $user_id = get_current_user_id();
            
            //get current alread read pages
            $read_pages		= (array)get_user_meta( $user_id, 'read_pages', true );
            
            //only add if not already there
            if(!in_array($post_id, $read_pages)){
                //add current page
                $read_pages[]	= $post_id;
        
                //update db
                update_user_meta( $user_id, 'read_pages', $read_pages);
            }
        }

        /* SHOW THE PDF */
        //Show the pdf fullscreen only if we are not a content manager
        if(!in_array('contentmanager', wp_get_current_user()->roles)){
            //Get the url to the pdf
            $pdf_url = $matches[1];
            
            //Convert to path
            $path = SIM\url_to_path($pdf_url);
            
            //Echo the pdf to screen
            ob_clean();
            ob_start();
            header("Content-type: application/pdf");
            header("Content-Disposition: inline; filename=".$matches[2]);
            @readfile($path);
            ob_end_flush(); 
        }
    }
	
	return $content;
});