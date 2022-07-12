<?php
namespace SIM\COMMENTS;
use SIM;
use SIM\ADMIN;

class CommentWarningEmail extends ADMIN\MailSetting{

    public $commentData;

    public function __construct($commentData) {
        // call parent constructor
		parent::__construct('unapproved_comment', MODULE_SLUG);

        $postId                 = $commentData['comment_post_ID'];
        $postTitle              = get_the_title($postId);
        $authorId               = get_post_field('post_author', $postId);
        $author                 = get_userdata($authorId);
        $commentId              = $commentData['comment_ID'];

        $approve_url            = admin_url( "comment.php?c=$commentId&action=approvecomment" );
        $delete_url             = admin_url( "comment.php?c=$commentId&action=trashcomment" );

        $this->addUser($author);

        $this->replaceArray['%comment_author%']     = $commentData['comment_author'];
        $this->replaceArray['%comment_content%']    = $commentData['comment_content'];
        $this->replaceArray['%post_author%']        = $author;
        $this->replaceArray['%post_title%']         = $postTitle;
        $this->replaceArray['%approve_link%']       = $approve_url;
        $this->replaceArray['%delete_link%']        = $delete_url;

        $this->defaultSubject   = "A new comment has been made on %post_title%";

        $this->defaultMessage    = 'Dear all,<br><br>';
		$this->defaultMessage   .= "%comment_author% just left a comment on %post_title%.<br>";
		$this->defaultMessage 	.= 'This is what the comment sais:<br>';
        $this->defaultMessage 	.= '%comment_content%<br><br>';
        $this->defaultMessage 	.= "Please approve this comment using <a href='%approve_link%'>this link</a><br>";
        $this->defaultMessage 	.= "You can delete this comment using <a href='%delete_link%'>this link</a><br>";
    }
}
