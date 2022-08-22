<?php
namespace SIM\EMBEDPAGE;
use SIM;

add_filter('sim_post_content', function($postContent){
    // Check if content is just an hyperlink
    //find all urls in the page
    $regex 	= '(?xi)\b((?:https?://|www\d{0,3}[.]|[a-z0-9.\-]+[.][a-z]{2,4}/)(?:[^\s()<>]+|\(([^\s()<>]+|(\([^\s()<>]+\)))*\))+(?:\(([^\s()<>]+|(\([^\s()<>]+\)))*\)|[^\s`!()\[\]{};:\'".,<>?«»“”‘’]))';
    preg_match_all("#$regex#i", $postContent, $matches);
    $url 	= $matches[0][0];

    //if the found url is the only post content
    if($url == strip_tags($postContent)){
        //find the post id of the url
        $postId	= url_to_postid($url);

        // If a valid post id
        if($postId > 0){
            $postContent	= "[embed_page id='$postId']";
        }
    }

    return $postContent;
});