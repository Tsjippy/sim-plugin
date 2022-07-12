<?php
namespace SIM\COMMENTS;
use SIM;

add_action( 'comment_post', function( $commentID, $approved, $commentdata ){
    $commentdata['commentID']   = $commentID;

    if($approved){
        // Comment reply
        if($commentdata['comment_parent'] > 0){
            $email                  = new CommentReplyEmail($commentdata);
            
            $parentComment          = get_comment($commentdata['comment_parent']);
            $parentCommentAuthor    = get_userdata($parentComment->user_id);

            $to                     = $parentCommentAuthor->user_email;
        // Send e-mail to the post author
        }else{
            $email                  = new ApprovedCommentEmail($commentdata);

            $postId                 = $commentdata['comment_post_ID'];
            $authorId               = get_post_field('post_author', $postId);
            $author                 = get_userdata($authorId);
            $to                     = $author->user_email;
        }
    // Send e-mail to content managers
    }else{
        $to                     = '';
        $users                  = get_users( ['role'    => 'editor'] );
        foreach($users as $user){
            $to .= $user->user_email.', ';
        }
        $email                  = new CommentWarningEmail($commentdata);
    }

    $email->filterMail();
    $subject                = $email->subject;
    $message                = $email->message;
    wp_mail( $to, $subject, $message);
    
}, 10, 3);

/**
 * Filter whether comments are open on post save
 *
 * @param string $status       Default status for the given post type,
 *                             either 'open' or 'closed'.
 * @param string $post_type    Post type. Default is `post`.
 * @param string $comment_type Type of comment. Default is `comment`.
 */
add_filter( 'get_default_comment_status', function ( $status, $post_type) {
    $allowedPostTypes     = SIM\getModuleOption(MODULE_SLUG, 'posttypes');
    if ( in_array($post_type, $allowedPostTypes)) {
        return 'open';
    }
 
    return $status;
}, 1, 2 );