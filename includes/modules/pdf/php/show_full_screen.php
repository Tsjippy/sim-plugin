<?php
namespace SIM\PDF;
use SIM;

//only load when checked
if(!SIM\getModuleOption(MODULE_SLUG, 'full_screen')){
    return;
}

function checkIfOnlyPdf($content){
    //If the string starts with 0 or more spaces, then a <p> followed by a hyperlink ending in .pdf then the download text ending an optional download button followed with 0 or more spaces.
    $pattern = '/^\s*<p><a href="(.*?\.pdf)">([^<]*<\/a>)(.*\.pdf">Download<\/a>)?<\/p>\s*$/i';
    
    //Execute the regex
    preg_match($pattern, $content, $matches);

    //If an url exists it means there is only a pdf on this page
    if(isset($matches[2])){
        return $matches;
    }

    return false;
}

//Show PDFs full screen
add_filter( 'the_content', function ( $content ) {
    $postId 	= get_the_ID();
    $content	= str_replace('<p>&nbsp;</p>','',$content);

    $matches    = checkIfOnlyPdf($content);
    
    //If an url exists it means there is only a pdf on this page
    if($matches){
        /* IF PEOPLE HAVE TO READ IT, MARK AS READ */
        $audience	= get_post_meta($postId, "audience", true);
        
        if(!empty($audience)){
            //Get current user id
            $userId = get_current_user_id();
            
            //get current alread read pages
            $readPages		= (array)get_user_meta( $userId, 'read_pages', true );
            
            //only add if not already there
            if(!in_array($postId, $readPages)){
                //add current page
                $readPages[]	= $postId;
        
                //update db
                update_user_meta( $userId, 'read_pages', $readPages);
            }
        }

        /* SHOW THE PDF */
        //Show the pdf fullscreen only if we are not a content manager
        if(
            !in_array('editor', wp_get_current_user()->roles)   &&  // We are not an editor
            (
                (
                    function_exists('SIM\CONTENTFILTER\isProtected') && // check if we should block this page function exists
                    !SIM\CONTENTFILTER\isProtected()                    // page is not protected
                )   ||
                !function_exists('SIM\CONTENTFILTER\isProtected')       // or the function does not exist
            )
        ){
            //Get the url to the pdf
            $pdfUrl = $matches[1];
            
            //Convert to path
            $path = SIM\urlToPath($pdfUrl);
            
            //Echo the pdf to screen
            SIM\clearOutput();
            header("Content-type: application/pdf");
            header("Content-Disposition: inline; filename=".$matches[2]);
            @readfile($path);
            ob_end_flush();
        }
    }
	
	return $content;
});

// add url to signal message
add_filter('sim_signal_post_notification_message', function( $excerpt, $post){
    // if this is a fullscreen pdf always return the url
    if(checkIfOnlyPdf($post->post_content)){
        return $excerpt."\n\n".get_permalink($post);
    }

    return $excerpt;
}, 10, 2);